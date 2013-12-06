<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AddressController extends AbstractActionController
{
    protected $_blockchain;

    public function __construct($blockchain)
    {/*{{{*/
        $this->_blockchain = $blockchain;
    }/*}}}*/

    public function indexAction()
    {/*{{{*/
        $address = $this->params('address');

        $addressData = $this->_blockchain->getAddressActivity($address);
        if (!$addressData) {
            $addressData = array('address' => null);
        }

        $view = new ViewModel($addressData);

        return $view;
    }/*}}}*/
}

