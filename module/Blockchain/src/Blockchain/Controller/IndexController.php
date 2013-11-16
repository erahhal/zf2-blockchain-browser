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
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $config = $this->getServiceLocator()->get('config');
        $client = new \Zend\Json\Server\Client($config['bitcoind']['server']);
        $blockcount = $client->call('getblockcount');
        for ($i = 0; $i < $blockcount; $i++) {
            $hash = $client->call('getblockhash', array($i));
            echo "$i: importing $hash\n";
            $block = $client->call('getblock', array($hash));
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
                $blockEntity->setNextblockhash($block['nextblockhash']);
            }
            if (isset($block['previousblockhash'])) {
                $blockEntity->setPreviousblockhash($block['previousblockhash']);
            }
            $objectManager->persist($blockEntity);
            $objectManager->flush();
        }

        return new ViewModel();
    }
}

