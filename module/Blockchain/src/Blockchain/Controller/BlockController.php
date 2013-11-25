<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class BlockController extends AbstractActionController
{

    public function indexAction()
    {
        $blockhash = $this->params('blockhash');
        $blocknumber = $this->params('blocknumber');

        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        if ($blockhash) {
            $blockEntity = $objectManager->getRepository('Blockchain\Entity\Block')->findOneBy(array('blockhash' => $blockhash));
        } else if ($blocknumber) {
            $blockEntity = $objectManager->getRepository('Blockchain\Entity\Block')->findOneBy(array('blockNumber' => $blocknumber));
        } else {
            return;
        }

        $view = new ViewModel(array(
            'number' => $blockEntity->getBlockNumber(),
            'blockhash' => $blockEntity->getBlockhash(),
            'previousblockhash' => $blockEntity->getPreviousblockhash(),
            'nextblockhash' => $blockEntity->getNextblockhash(),
            'difficulty' => $blockEntity->getDifficulty(),
            'bits' => $blockEntity->getBits(),
            'nonce' => $blockEntity->getNonce(),
            'merkleroot' => $blockEntity->getMerkleroot(),
            'time' => $blockEntity->getTime()->format('Y-m-d H:i:s'),
            'transactionCount' => $blockEntity->getTransactions()->count(),
            'totalBTC' => $blockEntity->getTotalvalue(),
            'size' => $blockEntity->getSize()
        ));

        return $view;
    }
}

