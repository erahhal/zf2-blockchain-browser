<?php

namespace Blockchain\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ChartController extends AbstractActionController
{
    protected $objectManager;

    public function dispatch(\Zend\Stdlib\RequestInterface $request, \Zend\Stdlib\ResponseInterface $response = null)
    {
        $this
            ->getServiceLocator()
            ->get('viewhelpermanager')
            ->get('InlineScript')
            ->appendFile('/js/home-charts.js')
            ;

        $this->objectManager = $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');

        return parent::dispatch($request, $response);
    }

    public function indexAction()
    {
        return new ViewModel();
    }

    public function circulationAction()
    {
        $startDate = $this->params('start-date');
        $endDate = $this->params('end-date');

        if (!$startDate) {
            $startDate = '2009-01-01';
        }
        $startDate = new \DateTime($startDate);
        if (!$endDate) {
            $endDate = date('Y-m-d');
        }
        $endDate = new \DateTime($endDate);

        $dateDiff = $startDate->diff($endDate);
        $maxPoints = 100;
        $maxLabels = 10;
        $labelInterval = floor($maxPoints / $maxLabels);
        $days = min($dateDiff->days, $maxPoints);
        
        $qb = $this->objectManager->createQueryBuilder();
        $qb->select('count(b.id)');
        $qb->from('Blockchain\Entity\Block','b');
        $blockCount = $qb->getQuery()->getSingleScalarResult();

        $interval = floor($blockCount / $days);

        $query = $this->objectManager->createQuery('SELECT b.time, b.blockNumber FROM Blockchain\Entity\Block b WHERE b.time >= :startDate AND b.time <= :endDate AND MOD(b.blockNumber, :interval) = 0')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->setParameter('interval', $interval)
            ;
        $result = $query->getResult();

        $jsChartData = 'var chartData = {
                labels : [';
        $count = 0;
        foreach($result as $blockData) {
            if ($count > 0) {
                $jsChartData .= ',';
            }
            if ($count % $labelInterval == 0) {
                $jsChartData .= "'".$blockData['time']->format('Y-m-d H:i:s')."'";
            } else {
                $jsChartData .= "''";
            }
            $count++;
        }
        $jsChartData .= '],
                datasets : [
                    {
                        fillColor : "rgba(151,187,205,0.5)",
                        strokeColor : "rgba(151,187,205,1)",
                        pointColor : "rgba(151,187,205,1)",
                        pointStrokeColor : "#fff",
                        data : [';
        $count = 0;
        $circulation = 0;
        foreach($result as $blockData) {
            if ($count > 0) {
                $jsChartData .= ',';
            }
            $coinbaseExp = floor(($blockData['blockNumber']) / 210000);
            $coinbaseValue = 50 / pow(2, $coinbaseExp);
            $circulation += $coinbaseValue;
            $jsChartData .= "$circulation";
            $count++;
        }

        $jsChartData .= '
                        ]
                    }
                ]
            }';
        
        return new ViewModel(array('chartData' => $jsChartData));
    }
}
