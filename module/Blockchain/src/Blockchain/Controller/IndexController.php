<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    protected $_blockchain;

    public function __construct($blockchain)
    {/*{{{*/
        $this->_blockchain = $blockchain;
    }/*}}}*/

    public function indexAction()
    {/*{{{*/
        $view = new ViewModel(array(
            'blockList' => $this->_blockchain->getLatestBlocks()
        ));

        return $view;
    }/*}}}*/

    public function importAction()
    {/*{{{*/
        $this->_blockchain->import();
        
        return new ViewModel();
    }/*}}}*/
}

