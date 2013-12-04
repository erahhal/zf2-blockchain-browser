<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class TransactionController extends AbstractActionController
{
    protected $_blockchain;

    public function __construct($blockchain)
    {/*{{{*/
        $this->_blockchain = $blockchain;
    }/*}}}*/

    public function indexAction()
    {/*{{{*/
        $txid = $this->params('txid');

        $transactionData = $this->_blockchain->getTransaction($txid);

        $view = new ViewModel($transactionData);

        return $view;
    }/*}}}*/
}

