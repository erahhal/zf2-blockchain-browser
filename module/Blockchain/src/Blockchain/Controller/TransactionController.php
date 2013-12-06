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
        $this
            ->getServiceLocator()
            ->get('viewhelpermanager')
            ->get('HeadScript')
            ->appendFile('/vendor/js/d3.v3.min.js')
            ->appendFile('/vendor/js/sankey.js')
            ;

        $txid = $this->params('txid');

        $transactionData = $this->_blockchain->getTransaction($txid);
        if ($transactionData) {
            $transactionData['chartHeight'] = max(count($transactionData['inputs']), count($transactionData['outputs'])) * 20;
            $nodes = array(array('index' => 0, 'name' => $transactionData['totalIn']));
            $links = array();
            $index = 1;
            foreach($transactionData['inputs'] as $input) {
                if ($input['isCoinbase']) {
                    $nodes[] = array('index' => $index, 'name' => $input['amount'].' ('.'Generation'.')'); 
                } else {
                    $nodes[] = array('index' => $index, 'name' => $input['amount'].' ('.$input['fromAddress'].')'); 
                }
                $links[] = array('source' => $index, 'target' => 0, 'value' => $input['amount']);
                $index++;
                if ($input['isCoinbase'] && $transactionData['fee'] < 0) {
                    $transactionData['chartHeight'] += 20;
                    $nodes[] = array('index' => $index, 'name' => abs($transactionData['fee']).' (fees)'); 
                    $links[] = array('source' => $index, 'target' => 0, 'value' => $transactionData['fee']);
                    $index++;
                }
            }
            foreach($transactionData['outputs'] as $output) {
                $nodes[] = array('index' => $index, 'name' => $output['amount'].' ('.$output['address'].')'); 
                $links[] = array('source' => 0, 'target' => $index, 'value' => $output['amount']);
                $index++;
            }
            if ($transactionData['fee'] > 0) {
                $nodes[] = array('index' => $index, 'name' => $transactionData['fee'].' (fees)'); 
                $links[] = array('source' => 0, 'target' => $index, 'value' => $transactionData['fee']);
                $index++;
            }
            $transactionData['nodes'] = $nodes;
            $transactionData['links'] = $links;
        } else {
            $transactionData = array('txid' => null);
        }

        $view = new ViewModel($transactionData);

        return $view;
    }/*}}}*/
}

