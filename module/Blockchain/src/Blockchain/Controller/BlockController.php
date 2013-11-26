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

        $transactions = array();
        foreach ($blockEntity->getTransactions() as $transactionEntity) {
            $transaction = array(
                'txid' => $transactionEntity->getTxid(),
                'fee' => $transactionEntity->getFee(),
                'size' => $transactionEntity->getSize(),
                'from' => array(),
                'to' => array()
            );
            foreach ($transactionEntity->getInputs() as $inputEntity) {
                if ($inputEntity->getCoinbase()) {
                    $input = array('type' => 'coinbase', 'amount' => $inputEntity->getValue(), 'takenFees' => $blockEntity->getTakenFees());
                } else {
                    $input = array('type' => 'pubkey', 'amount' => $inputEntity->getValue(), 'address' => $inputEntity->getAddress());
                }
                $transaction['from'][] = $input;
            }
            foreach ($transactionEntity->getOutputs() as $outputEntity) {
                $output = array('amount' => $outputEntity->getValue(), 'address' => $outputEntity->getAddress());
                $transaction['to'][] = $output;
            }

            $transactions[] = $transaction;
        }

        $view = new ViewModel(array(
            'blocknumber' => $blockEntity->getBlockNumber(),
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
            'lostBTC' => $blockEntity->getLostValue(),
            'offeredFees' => $blockEntity->getOfferedFees(),
            'takenFees' => $blockEntity->getTakenFees(),
            'size' => $blockEntity->getSize(),
            'transactions' => $transactions
        ));

        return $view;
    }
}

