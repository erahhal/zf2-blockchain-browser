<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function indexAction() {
        $this
            ->getServiceLocator()
            ->get('viewhelpermanager')
            ->get('InlineScript')
            ->appendFile('js/home-charts.js')
            ;
    }

    public function indexActionDB() {
        $objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        $user = new \Application\Entity\User();
        $user->setFirstName('Marco');
        $user->setLastName('Pivetta');

        $objectManager->persist($user);
        $objectManager->flush();

        // die(var_dump($user->getId())); // yes, I'm lazy
    }

    public function indexActionOrig()
    {
        return new ViewModel();
    }
}
