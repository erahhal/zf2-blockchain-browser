<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Mvc\ModuleRouteListener;
use Zend\Mvc\MvcEvent;

class Module
{
    public function onBootstrap(MvcEvent $e)
    {
        $eventManager        = $e->getApplication()->getEventManager();
        $moduleRouteListener = new ModuleRouteListener();
        $moduleRouteListener->attach($eventManager);

        $eventManager->attach(MvcEvent::EVENT_FINISH, array($this, 'onFinish'));

        $this->app = $e->getApplication();
        $this->serviceManager = $this->app->getServiceManager();
        $this->config = $this->serviceManager->get('config');

        if (extension_loaded('xhprof')) {
            if ($this->config['xhprof']['enabled']) {
                ini_set('xhprof.output_dir', $this->config['xhprof']['outputDir']);
                include_once($this->config['xhprof']['libPath'].'xhprof_lib/utils/xhprof_lib.php');
                include_once($this->config['xhprof']['libPath'].'xhprof_lib/utils/xhprof_runs.php');
                xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
            } else {
                xhprof_disable();
            }
        }
    }

    public function onFinish(MvcEvent $e)
    {
        if (extension_loaded('xhprof') && $this->config['xhprof']['enabled']) {
            $content = $e->getResponse()->getContent();
            if (preg_match('/<body>/', $content)) {
                $profilerNamespace = 'onrush';  // namespace for your application
                $xhprofData = xhprof_disable();
                $xhprofRuns = new \XHProfRuns_Default();
                $runId = $xhprofRuns->save_run($xhprofData, $profilerNamespace);
                if ($this->config['xhprof']['viewLink']) {
                    $link = '<div class="profiler"><a href="/profiler/run/'.$runId.'/source/'.$profilerNamespace.'" target="_blank">Profiler output</a></div>'; 
                    $injected = preg_replace('/<\/body>/i', $link . "\n</body>", $content, 1);
                    $e->getResponse()->setContent($injected);
                }   
            }
        }           
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
}
