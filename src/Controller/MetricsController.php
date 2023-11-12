<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Controller;

use Prometheus\CollectorRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MetricsController extends AbstractController
{
    public function index(Request $request): Response
    {
        \Prometheus\Storage\Redis::setDefaultOptions(
            [
                'host' => 'redis',
                'port' => 6379,
                'password' => null,
                'timeout' => 0.1, // in seconds
                'read_timeout' => '10', // in seconds
                'persistent_connections' => false
            ]
        );


        $registry = \Prometheus\CollectorRegistry::getDefault();
        //$registry = new CollectorRegistry(new \Prometheus\Storage\APC());

        $renderer = new \Prometheus\RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        return new Response($result, Response::HTTP_OK, [
            'Content-type' => \Prometheus\RenderTextFormat::MIME_TYPE,
        ]);
    }
}
