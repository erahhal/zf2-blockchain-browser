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
                        'controller' => 'Profiler\Controller\Index',
                        'action'     => 'index',
                    ),
                    */
                ),
            ),
            // The following is a route to simplify getting started creating
            // new controllers and actions without needing to create a new
            // module. Simply drop new controllers in, and you can access them
            // using the path /profiler/:controller/:action
            'profiler' => array(
                'type'    => 'Literal',
                'options' => array(
                    'route'    => '/profiler',
                    'defaults' => array(
                        '__NAMESPACE__' => 'Profiler\Controller',
                        'controller'    => 'Index',
                        'action'        => 'index',
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
                    'index-source' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/run/:run/source/:source',
                            'defaults' => array(
                                'controller' => 'index',
                                'action' => 'index',
                            ),
                        ),
                    ),
                    'index-symbol' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/run/:run/symbol/:symbol',
                            'defaults' => array(
                                'controller' => 'index',
                                'action' => 'index',
                            ),
                        ),
                    ),
                    'index-source-symbol' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/run/:run/symbol/:symbol/source/:source',
                            'defaults' => array(
                                'controller' => 'index',
                                'action' => 'index',
                            ),
                        ),
                    ),
                    'index-source-all' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/run/:run/all/:all/source/:source',
                            'defaults' => array(
                                'controller' => 'index',
                                'action' => 'index',
                            ),
                        ),
                    ),
                    'callgraph-source' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/callgraph/run/:run/source/:source',
                            'defaults' => array(
                                'controller' => 'index',
                                'action' => 'callgraph',
                            ),
                        ),
                    ),
                    'callgraph-source-all' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/callgraph/run/:run/source/:source/all/:all',
                            'defaults' => array(
                                'controller' => 'index',
                                'action' => 'callgraph',
                            ),
                        ),
                    ),
                    'callgraph-symbol-func-source' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/callgraph/run/:run/symbol/:symbol/func/:func/source/:source',
                            'defaults' => array(
                                'controller' => 'index',
                                'action' => 'callgraph',
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
            'Profiler\Controller\Index' => 'Profiler\Controller\IndexController'
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
            'profiler/index/index' => __DIR__ . '/../view/profiler/index/index.phtml',
            'error/404'               => __DIR__ . '/../../Application/view/error/404.phtml',
            'error/index'             => __DIR__ . '/../../Application/view/error/index.phtml',
        ),
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
    'console' => array(
        'router' => array(
            'routes' => array(
            )
        )
    ),
    'doctrine' => array(
        'driver' => array(
            'Profiler_driver' => array(
                'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'cache' => 'array',
                'paths' => array(__DIR__ . '/../src/Profiler/Entity')
            ),

            'orm_default' => array(
                'drivers' => array(
                    'Profiler\Entity' => 'Profiler_driver'
                )
            )
        )
    )
);
