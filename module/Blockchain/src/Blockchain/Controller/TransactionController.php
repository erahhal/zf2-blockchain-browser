<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class TransactionController extends AbstractActionController
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
        $block = $this->getBlock('0000000010b0fa7c35d417b232cc849a5d4701e3716f547f4006b599bbfa7269');
        // $tx = $this->getRawTransaction('b9a5eaf6854ec6dda9571ca6e8399cbd23a97834b1401c8669816d937e3b8683');
        // $tx = $this->getRawTransaction('43eaedf3b6239f8f47193a605f8591b31b8112cd04b20e95f4edc9b2de165a89');
        // die(print_r($tx, true));

        $hash = $client->call('getblockhash', array(80000));
        $batchCount = 1;
        $batchSize = 100; 
        // Start importing newer blocks
        while($hash) {
            echo "Importing Transactions for Block: $batchCount\n";

            $block = $this->getBlock('00000000033e7716aa8657aff4423c3fdbc99725e1edc6dfbeaaa22d06c156ad');

            $count = 0;
            foreach ($block['tx'] as $txHash) {
                $count++;
                // the JSON RPC client appears to have a memory leak, so isolate it inside a function
                // $transaction = $this->getRawTransaction($txHash);
                $transaction = $this->getRawTransaction('0e3e2357e806b6cdb1f70b54c3a3a17b6714ee1f0e68bebb44a74b1efd512098');
                    die(print_r($transaction, true));
                
                if (!$transaction) {
                    die('failure retrieving transaction');
                }

                /*
                $transactionEntity = new \Blockchain\Entity\Block();
                $transactionEntity->setHash($block['hash']);
                $transactionEntity->setSize($block['size']);
                $transactionEntity->setHeight($block['height']);
                $transactionEntity->setVersion($block['version']);
                $transactionEntity->setMerkleroot($block['merkleroot']);
                $transactionEntity->setTime(new \DateTime('@'.$block['time']));
                $transactionEntity->setNonce($block['nonce']);
                $transactionEntity->setBits($block['bits']);
                $transactionEntity->setDifficulty($block['difficulty']);
                if (isset($block['nextblockhash'])) {
                    $hash = $block['nextblockhash'];
                    $transactionEntity->setNextblockhash($block['nextblockhash']);
                } else {
                    $hash = null;
                }
                if (isset($block['previousblockhash'])) {
                    $transactionEntity->setPreviousblockhash($block['previousblockhash']);
                }
                $entityManager->persist($transactionEntity);
                if ($batchCount % $batchSize == 0 || !$hash) {
                    echo "\nwriting data to DB...\n\n";
                    $entityManager->flush();
                    $entityManager->clear();
                }
                */
                $batchCount++;
            }
        }
        
        return new ViewModel();
    }

    // the JSON RPC client appears to have a memory leak, so isolate it inside a function
    protected function getBlock($hash)
    {/*{{{*/
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
    }/*}}}*/

    // the JSON RPC client appears to have a memory leak, so isolate it inside a function
    protected function getTransaction($hash)
    {/*{{{*/
        $client = new \Zend\Json\Server\Client($this->config['bitcoind']['server']);
        $client->getHttpClient()->setOptions(array('timeout' => 30));

        $retryCount = 3;
        $transaction = null;
        do {
            $queryFailed = false;
            try {
                $transaction = $client->call('gettransaction', array($hash));
            } catch (Zend\Http\Client\Adapter\Exception $e) {
                echo $e->getMessage();
                $queryFailed = true;
                // sleep 100ms
                usleep(200000);
            }
            $retryCount--;
        } while ((!$transaction || $queryFailed) && $retryCount);

        return $transaction;
    }/*}}}*/

    // the JSON RPC client appears to have a memory leak, so isolate it inside a function
    protected function getRawTransaction($hash)
    {/*{{{*/
        $client = new \Zend\Json\Server\Client($this->config['bitcoind']['server']);
        $client->getHttpClient()->setOptions(array('timeout' => 30));

        $retryCount = 3;
        $transaction = null;
        do {
            $queryFailed = false;
            try {
                $rawTransaction = $client->call('getrawtransaction', array($hash));
                $transaction = $client->call('decoderawtransaction', array($rawTransaction));
            } catch (Zend\Http\Client\Adapter\Exception $e) {
                echo $e->getMessage();
                $queryFailed = true;
                // sleep 100ms
                usleep(200000);
            }
            $retryCount--;
        } while ((!$transaction || $queryFailed) && $retryCount);

        return $transaction;
    }/*}}}*/
}

