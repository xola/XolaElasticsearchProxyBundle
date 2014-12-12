<?php

namespace Xola\ElasticsearchProxyBundle\Tests\Controller;

use Xola\ElasticsearchProxyBundle\Controller\ElasticsearchProxyController;
use Xola\ElasticsearchProxyBundle\Tests\Mocks\MockUser;

class ElasticsearchProxyControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $params
     *
     * @return ElasticsearchProxyController
     */
    public function buildController($params = array())
    {
        $container = $this->getMock("Symfony\Component\DependencyInjection\ContainerInterface");
        $config = array(
            'client' => array(
                'indexes' => array('logs'),
                'host' => 'localhost',
                'protocol' => 'http',
                'port' => '9200'
            ),
            'roles_skip_auth_filter' => array()
        );
        $container->expects($this->any())
            ->method("getParameter")
            ->with("xola_elasticsearch_proxy")
            ->will($this->returnValue($config));
        $defaults['container'] = $container;

        $user = $this->getMock("Xola\ElasticsearchProxyBundle\Tests\Mocks\MockUser");
        $user->expects($this->any())
            ->method("getId")
            ->will($this->returnValue('123'));
        $defaults['user'] = $user;
        $params = array_merge($defaults, $params);

        // Get controller with mocked getUser method
        $controllerBuilder = $this->getMockBuilder(
            'Xola\ElasticsearchProxyBundle\Controller\ElasticsearchProxyController'
        );
        $controller = $controllerBuilder->setMethods(array('getUser'))->getMock();
        $controller->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($params['user']));

        $controller->setContainer($params['container']);

        return $controller;
    }

    public function testGetElasticSearchUrl()
    {
        $controller = $this->buildController();
        $url = $controller->getElasticSearchUrl('pretty=true', 'logs', '_search');

        $this->assertEquals('http://localhost:9200/logs/_search?pretty=true', $url, 'Should be url built from proxy');
    }
}