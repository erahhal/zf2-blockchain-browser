<?php
namespace Blockchain;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function getControllerConfig()
    {
        return array(
            'factories' => array(
                'Blockchain\Controller\Index' => function ($serviceManager) {
                    $blockchain = $serviceManager->getServiceLocator()->get('Blockchain');
                    $controller = new \Blockchain\Controller\IndexController($blockchain);
                    return $controller;
                },
                'Blockchain\Controller\Block' => function ($serviceManager) {
                    $blockchain = $serviceManager->getServiceLocator()->get('Blockchain');
                    $controller = new \Blockchain\Controller\BlockController($blockchain);
                    return $controller;
                },
                'Blockchain\Controller\Transaction' => function ($serviceManager) {
                    $blockchain = $serviceManager->getServiceLocator()->get('Blockchain');
                    $controller = new \Blockchain\Controller\TransactionController($blockchain);
                    return $controller;
                },
                'Blockchain\Controller\Address' => function ($serviceManager) {
                    $blockchain = $serviceManager->getServiceLocator()->get('Blockchain');
                    $controller = new \Blockchain\Controller\AddressController($blockchain);
                    return $controller;
                },
                'Blockchain\Controller\Chart' => function ($serviceManager) {
                    $blockchain = $serviceManager->getServiceLocator()->get('Blockchain');
                    $controller = new \Blockchain\Controller\ChartController($blockchain);
                    return $controller;
                },
            ),
        );
    }

    public function getServiceConfig()
    {
        return array(
            'factories' => array(
                'Blockchain' => function($serviceManager) {
                    $objectManager = $serviceManager
                        ->get('Doctrine\ORM\EntityManager');

                    $config = $serviceManager->get('config');

                    $blockchain = new Model\Blockchain(array(
                        'objectManager' => $objectManager,
                        'bitcoindServerUrl' => $config['bitcoind']['server'],
                    ));

                    return $blockchain;
                },
            ),
        );
    }
}
