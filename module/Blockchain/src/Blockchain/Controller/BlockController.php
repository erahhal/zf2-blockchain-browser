<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class BlockController extends AbstractActionController
{
    protected $_blockchain;

    public function __construct($blockchain)
    {/*{{{*/
        $this->_blockchain = $blockchain;
    }/*}}}*/

    public function indexAction()
    {/*{{{*/
        $blockhash = $this->params('blockhash');
        $blocknumber = $this->params('blocknumber');

        if ($blockhash) {
            $blockData = $this->_blockchain->getBlockByHash($blockhash);
        } else if ($blocknumber) {
            $blockData = $this->_blockchain->getBlockByNumber($blocknumber);
        } else {
            return;
        }

        $view = new ViewModel($blockData);

        return $view;
    }/*}}}*/
}

