<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class TransactionController extends AbstractActionController
{

    public function indexAction()
    {
        return new ViewModel();
    }
}

