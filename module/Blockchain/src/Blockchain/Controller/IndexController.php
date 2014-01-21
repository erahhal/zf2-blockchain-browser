<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{

    protected $_blockchain = null;

    public function __construct($blockchain)
    {/*{{{*/
        $this->_blockchain = $blockchain;
    }/*}}}*/

    public function indexAction()
    {/*{{{*/
        $blockPaginator = $this->_blockchain->getBlocks();
        $blockPaginator->setCurrentPageNumber((int) $this->params('page'));

        $view = new ViewModel(array(
            'blockPaginator' => $blockPaginator
        ));

        return $view;
    }/*}}}*/

    public function importAction()
    {/*{{{*/
        $this->_blockchain->import();
        
        return new ViewModel();
    }/*}}}*/

    public function searchAction()
    {/*{{{*/
        $phrase = $this->params()->fromQuery('phrase');
        $results = $this->_blockchain->search($phrase);
        if ($results['success']) {
            switch($results['type']) {
                case 'blocknumber':
                    $formatted = array(
                        'name' => 'Block Number '.$results['id'],
                        'url' => '/blockchain/block/number/'.$results['id'],
                    );
                    $redirect = $this->redirect()->toRoute('blockchain/block-number', array(
                        'blocknumber' => $results['id'],
                    ));
                    break;
                case 'blockhash':
                    $formatted = array(
                        'name' => 'Block Hash '.$results['id'],
                        'url' => '/blockchain/block/hash/'.$results['id'],
                    );
                    $redirect = $this->redirect()->toRoute('blockchain/block-hash', array(
                        'blockhash' => $results['id'],
                    ));
                    break;
                case 'address':
                    $formatted = array(
                        'name' => 'Address '.$results['id'],
                        'url' => '/blockchain/address/'.$results['id'],
                    );
                    $redirect = $this->redirect()->toRoute('blockchain/address', array(
                        'address' => $results['id'],
                    ));
                    break;
                case 'txid':
                    $formatted = array(
                        'name' => 'Transaction '.$results['id'],
                        'url' => '/blockchain/transaction/'.$results['id'],
                    );
                    $redirect = $this->redirect()->toRoute('blockchain/transaction', array(
                        'txid' => $results['id'],
                    ));
                    break;
            }
            return $redirect; 
        } else {
            $formatted = array(
                'name' => $results['message'],
                'url' => null
            );
            return new ViewModel($formatted);
        }
    }/*}}}*/
}

