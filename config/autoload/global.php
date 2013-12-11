<?php
/**
 * Global Configuration Override
 *
 * You can use this file for overriding configuration values from modules, etc.
 * You would place values in here that are agnostic to the environment and not
 * sensitive to security.
 *
 * @NOTE: In practice, this file will typically be INCLUDED in your source
 * control, so do not include passwords or other sensitive information in this
 * file.
 */

return array(
    'bitcoind' => array(
        'server' => 'http://bitcoinrpc:4mWvdrWsVHJh2tufUfvJqUNBfQkqe2nKTD7gJhU9mjBF@127.0.0.1:8332/'
    ),
    'xhprof' => array(
        'enabled' => true,
        'libPath' => 'vendor/facebook/xhprof/',
        'outputDir' => 'data/xhprof',
        'viewLink' => true
    ),
    'navigation' => array(
        'default' => array(
            array(
                'label' => 'Home',
                'route' => 'home',
            ),
            array(
                'label' => 'Blockchain Browser',
                'route' => 'blockchain',
                /*
                'pages' => array(
                    array(
                        'label' => 'Block',
                        'route' => 'blockchain/block-number',
                        'params' => array(
                            'blocknumber' => '1',
                        ),
                    ),
                ),
                */
            ),
            /* 
            array(
                'label' => 'Charts',
                'route' => 'blockchain/charts',
            ),
             */
        ),
    ),
    'service_manager' => array(
        'factories' => array(
            'navigation' => 'Zend\Navigation\Service\DefaultNavigationFactory',
        ),
    ),
);
