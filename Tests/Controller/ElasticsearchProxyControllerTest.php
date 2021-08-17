<?php

namespace Xola\ElasticsearchProxyBundle\Tests\Controller;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Xola\ElasticsearchProxyBundle\Controller\ElasticsearchProxyController;

class ElasticsearchProxyControllerTest extends TestCase
{
    public function testGetElasticSearchUrl()
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
        $container->expects($this->once())->method("getParameter")->with("xola_elasticsearch_proxy")
            ->willReturn($config);
        $controller = new ElasticsearchProxyController();

        $url = $controller->getElasticSearchUrl('pretty=true', 'logs', '_search', $container);

        $this->assertEquals('http://localhost:9200/logs/_search?pretty=true', $url, 'Should be url built from proxy');
    }
}
