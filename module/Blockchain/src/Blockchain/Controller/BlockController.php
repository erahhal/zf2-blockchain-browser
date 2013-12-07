<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class BlockController extends AbstractActionController
{

    protected $_blockchain = null;

    public function __construct($blockchain)
    {
        {/*{{{*/
                $this->_blockchain = $blockchain;
            }/*}}}*/
    }

    public function indexAction()
    {/*{{{*/
        $blocknumber = $this->params('blocknumber');
        $blockhash = $this->params('blockhash');

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

    public function largestAction()
    {/*{{{*/
        $blocknumber = $this->_blockchain->getLargestBlock();
        $redirect = $this->redirect()->toRoute('blockchain/block-number', array(
            'blocknumber' => $blocknumber,
        ));
    }/*}}}*/

    public function priciestAction()
    {/*{{{*/
        $blocknumber = $this->_blockchain->getPriciestBlock();
        $redirect = $this->redirect()->toRoute('blockchain/block-number', array(
            'blocknumber' => $blocknumber,
        ));
    }/*}}}*/


}

