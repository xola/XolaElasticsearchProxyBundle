<?php

namespace Xola\ElasticsearchProxyBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Xola\ElasticsearchProxyBundle\Controller\ElasticsearchProxyController;

class ElasticSearchProxyControllerTest extends TestCase
{
    /** @var ElasticsearchProxyController */
    private $controller;

    protected function setUp(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $config = array(
            'client' => array(
                'indexes' => array('logs'),
                'host' => 'localhost',
                'protocol' => 'http',
                'port' => '9200'
            ),
            'roles_skip_auth_filter' => array()
        );
        $container->expects($this->any())->method("getParameter")->with("xola_elasticsearch_proxy")
            ->willReturn($config);

        $this->controller = new ElasticsearchProxyController();
        $this->controller->setContainer($container);
    }


    public function testGetElasticSearchUrl()
    {
        $url = $this->controller->getElasticSearchUrl('pretty=true', 'logs', '_search');

        $this->assertEquals('http://localhost:9200/logs/_search?pretty=true', $url, 'Should be url built from proxy');
    }

}
