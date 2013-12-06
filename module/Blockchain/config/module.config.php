<?php
return array(
    'router' => array(
        'routes' => array(
            'home' => array(
                'type' => 'Zend\Mvc\Router\Http\Literal',
                'options' => array(
                    'route'    => '/',
                    /*
                    // This will override the application default, so add
                    // only if you want to make this the home page
                    'defaults' => array(
                        'controller' => 'Blockchain\Controller\Index',
                        'action'     => 'index',
                    ),
                    */
                ),
            ),
            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /blockchain/:controller/:action
            'blockchain' => array(
                'type'    => 'Segment',
                'options' => array(
                    'route'    => '/blockchain[/page/:page]',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Blockchain\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
                        'page'          => 1,
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'default' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action'     => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                    'block-number' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/block/number/:blocknumber',
                            'constraints' => array(
                                'blocknumber' => '[0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Blockchain\Controller\Block',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'block-hash' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/block/hash/:blockhash',
                            'constraints' => array(
                                'blockhash' => '[\\xa-fA-F0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Blockchain\Controller\Block',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'transaction' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/transaction/:txid',
                            'constraints' => array(
                                'txid' => '[\\xa-fA-F0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Blockchain\Controller\Transaction',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'address' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/address/:address',
                            'constraints' => array(
                                'address' => '[a-zA-Z0-9]+',
                            ),
                            'defaults' => array(
                                'controller' => 'Blockchain\Controller\Address',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'charts' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/charts',
                            'constraints' => array(
                            ),
                            'defaults' => array(
                                'controller' => 'Blockchain\Controller\Chart',
                                'action'     => 'index',
                            ),
                        ),
                    ),
                    'search' => array(
                        'type'    => 'Segment',
                        'options' => array(
                            'route'    => '/search',
                            'constraints' => array(
                            ),
                            'defaults' => array(
                                'controller' => 'Blockchain\Controller\Index',
                                'action'     => 'search',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'service_manager' => array(
        'abstract_factories' => array(
            'Zend\Cache\Service\StorageCacheAbstractServiceFactory',
            'Zend\Log\LoggerAbstractServiceFactory',
        ),
        'aliases' => array(
            'translator' => 'MvcTranslator',
        ),
    ),
    'translator' => array(
        'locale' => 'en_US',
        'translation_file_patterns' => array(
            array(
                'type'     => 'gettext',
                'base_dir' => __DIR__ . '/../language',
                'pattern'  => '%s.mo',
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            // 'Blockchain\Controller\Index' => 'Blockchain\Controller\IndexController',
            // 'Blockchain\Controller\Block' => 'Blockchain\Controller\BlockController',
            // 'Blockchain\Controller\Transaction' => 'Blockchain\Controller\TransactionController',
            // 'Blockchain\Controller\Address' => 'Blockchain\Controller\AddressController',
            // 'Blockchain\Controller\Chart' => 'Blockchain\Controller\ChartController',
        ),
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => array(
            'layout/layout'           => __DIR__ . '/../../Application/view/layout/layout.phtml',
            'blockchain/index/index' => __DIR__ . '/../view/blockchain/index/index.phtml',
            'error/404'               => __DIR__ . '/../../Application/view/error/404.phtml',
            'error/index'             => __DIR__ . '/../../Application/view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
        'strategies' => array(
            'Mustache\View\Strategy',
        ),
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
                'import-blockchain' => array(
                    'type' => 'simple',
                    'options' => array(
                        'route'    => 'import-blockchain',
                        'defaults' => array(
                            'controller' => 'Blockchain\Controller\Index',
                            'action'     => 'import',
                        )
                    )
                )
            )
        )
    ),
    'doctrine' => array(
        'driver' => array(
            'Blockchain_driver' => array(
                'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Blockchain/Entity')
            ),

            'orm_default' => array(
                'drivers' => array(
                    'Blockchain\Entity' => 'Blockchain_driver'
                )
            )
        )
    )
);
