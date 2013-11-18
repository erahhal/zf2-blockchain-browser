<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{

    public function indexAction()
    {
        return new ViewModel();
    }

    public function loadAction()
    {
        $this->config = $this->getServiceLocator()->get('config');

        // this can take a long time
        set_time_limit(0);

        $entityManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $client = new \Zend\Json\Server\Client($this->config['bitcoind']['server']);
        $client->getHttpClient()->setOptions(array('timeout' => 30));
        $blockcount = $client->call('getblockcount');

        // Get the last block in the DB
        $query = $entityManager->createQuery('SELECT b FROM Blockchain\Entity\Block b WHERE b.id = (SELECT MAX(bm.id) FROM Blockchain\Entity\Block bm)');
        $result = $query->getResult();
        if (count($result) == 1) {
            // always redo the last hash in the DB, because it might have a "nextblockhash" when it was null before
            $blockEntity = $result[0];
            $hash = $blockEntity->getHash();
            $block = $this->getBlock($hash);
            if (isset($block['nextblockhash']) && !$blockEntity->getNextblockhash()) {
                $blockEntity->setNextblockhash($block['nextblockhash']);
                $entityManager->flush();
                $entityManager->clear();
            }
            $hash = $blockEntity->getNextblockhash();
            $batchCount = $blockEntity->getId() + 1;
        } else {
            $hash = $client->call('getblockhash', array(1));
            $batchCount = 1;
        }

        $batchSize = 100; 
        // Start importing newer blocks
        while($hash) {
            echo "Importing Block: $batchCount, Hash: $hash\n";

            // the JSON RPC client appears to have a memory leak, so isolate it inside a function
            $block = $this->getBlock($hash);
            
            if (!$block) {
                die('failure retrieving block');
            }

            $blockEntity = new \Blockchain\Entity\Block();
            $blockEntity->setHash($block['hash']);
            $blockEntity->setSize($block['size']);
            $blockEntity->setHeight($block['height']);
            $blockEntity->setVersion($block['version']);
            $blockEntity->setMerkleroot($block['merkleroot']);
            $blockEntity->setTime(new \DateTime('@'.$block['time']));
            $blockEntity->setNonce($block['nonce']);
            $blockEntity->setBits($block['bits']);
            $blockEntity->setDifficulty($block['difficulty']);
            if (isset($block['nextblockhash'])) {
                $hash = $block['nextblockhash'];
                $blockEntity->setNextblockhash($block['nextblockhash']);
            } else {
                $hash = null;
            }
            if (isset($block['previousblockhash'])) {
                $blockEntity->setPreviousblockhash($block['previousblockhash']);
            }
            $entityManager->persist($blockEntity);
            if ($batchCount % $batchSize == 0 || !$hash) {
                echo "\nwriting data to DB...\n\n";
                $entityManager->flush();
                $entityManager->clear();
            }
            $batchCount++;
        }
        
        return new ViewModel();
    }

    // the JSON RPC client appears to have a memory leak, so isolate it inside a function
    protected function getBlock($hash)
    {
        $client = new \Zend\Json\Server\Client($this->config['bitcoind']['server']);
        $client->getHttpClient()->setOptions(array('timeout' => 30));

        $retryCount = 3;
        $block = null;
        do {
            $queryFailed = false;
            try {
                $block = $client->call('getblock', array($hash));
            } catch (Zend\Http\Client\Adapter\Exception $e) {
                echo $e->getMessage();
                $queryFailed = true;
                // sleep 100ms
                usleep(200000);
            }
            $retryCount--;
        } while ((!$block || $queryFailed) && $retryCount);

        return $block;
    }
}

