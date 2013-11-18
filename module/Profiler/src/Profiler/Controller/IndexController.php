<?php

namespace Profiler\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class IndexController extends AbstractActionController
{

    public function indexAction()
    {
        $this->config = $this->getServiceLocator()->get('config');
        ini_set('memory_limit', '512M');
        ini_set('xhprof.output_dir', $this->config['xhprof']['outputDir']);
        error_reporting(E_ERROR | E_PARSE);

        $run = $this->params('run');
        $wts = $this->params('wts');
        $symbol = $this->params('symbol');
        $func = $this->params('func');
        $sort = $this->params('sort');
        $run1 = $this->params('run1');
        $run2 = $this->params('run2');
        $source = $this->params('source');
        $all = $this->params('all');


        ob_start();
        include($this->config['xhprof']['libPath'].'xhprof_html/index.php');
        $content = ob_get_clean();

        // fix URLs
        $content = preg_replace('/\/css\/xhprof.css/', '/vendor/xhprof_html/css/xhprof.css', $content);
        // $content = preg_replace("/<script src='\/jquery\/jquery-1.2.6.js'><\/script>/", '', $content);  // remove Jquery, as it's already included
        $content = preg_replace('/\/jquery\/jquery-1.2.6.js/', '/vendor/xhprof_html/jquery/jquery-1.2.6.js', $content);
        $content = preg_replace('/\/jquery\/jquery.tooltip.css/', '/vendor/xhprof_html/jquery/jquery.tooltip.css', $content);
        $content = preg_replace('/\/jquery\/jquery.tooltip.js/', '/vendor/xhprof_html/jquery/jquery.tooltip.js', $content);
        $content = preg_replace('/\/jquery\/jquery.autocomplete.css/', '/vendor/xhprof_html/jquery/jquery.autocomplete.css', $content);
        $content = preg_replace('/\/jquery\/jquery.autocomplete.js/', '/vendor/xhprof_html/jquery/jquery.autocomplete.js', $content);
        $content = preg_replace('/\/js\/xhprof_report.js/', '/vendor/xhprof_html/js/xhprof_report.js', $content);
        $content = preg_replace("/'\/callgraph.php\?run=([^']+)&symbol=([^']+)&source=([^']+)&func=([^']+)'/", '"/profiler/callgraph/run/$1/symbol/$2/func/$4/source/$3"', $content);
        $content = preg_replace('/"\/callgraph.php\?run=([^"]+)&source=([^"]+)&all=([^"]+)"/', '"/profiler/callgraph/run/$1/source/$2/all/$3"', $content);
        $content = preg_replace('/"\/callgraph.php\?run=([^"]+)&source=([^"]+)"/', '"/profiler/callgraph/run/$1/source/$2"', $content);
        $content = preg_replace('/"\/\?run=([^"]+?)&source=([^"]+?)&symbol=([^"]+?)"/', '"/profiler/run/$1/symbol/$3/source/$2"', $content);
        $content = preg_replace('/"\/\?run=([^"]+?)&source=([^"]+?)&all=([^"]+?)"/', '"/profiler/run/$1/all/$3/source/$2"', $content);
        $content = preg_replace('/"\/\?run=([^"]+?)&source=([^"]+?)"/', '"/profiler/run/$1/source/$2"', $content);
        $content = preg_replace('/"\/\?run=([^"]+?)&symbol=([^"]+?)"/', '"/profiler/run/$1/symbol/$2"', $content);

        return new ViewModel(array('content' => $content));
    }

    public function callgraphAction()
    {
        $this->config = $this->getServiceLocator()->get('config');
        ini_set('memory_limit', '512M');
        ini_set('xhprof.output_dir', $this->config['xhprof']['outputDir']);
        error_reporting(E_ERROR | E_PARSE);

        $run = $this->params('run');
        $wts = $this->params('wts');
        $symbol = $this->params('symbol');
        $func = $this->params('func');
        $sort = $this->params('sort');
        $run1 = $this->params('run1');
        $run2 = $this->params('run2');
        $source = $this->params('source');
        $all = $this->params('all');

        ob_start();
        include($this->config['xhprof']['libPath'].'xhprof_html/callgraph.php');
        $imageContent = ob_get_clean();

        // get image content
        $response = $this->getResponse();

        $response->setContent($imageContent);
        $response
            ->getHeaders()
            ->addHeaderLine('Content-Transfer-Encoding', 'binary')
            ->addHeaderLine('Content-Type', 'image/png')
            ->addHeaderLine('Content-Length', mb_strlen($imageContent));

        return $response;
    }


}

