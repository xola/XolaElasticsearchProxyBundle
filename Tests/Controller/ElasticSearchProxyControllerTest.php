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

    public function testGetAuthorisationFilter()
    {
        $controller = $this->buildController();
        $authFilter = $controller->getAuthorisationFilter();

        $this->assertEquals('123', $authFilter['term']['seller.id']);
    }

    public function testGetAuthorisationFilterForSkippingAuthFilter()
    {
        $container = $this->getMock("Symfony\Component\DependencyInjection\ContainerInterface");
        $config = array(
            'roles_skip_auth_filter' => array('ROLE_ADMIN')
        );
        $container->expects($this->any())
            ->method("getParameter")
            ->with("xola_elasticsearch_proxy")
            ->will($this->returnValue($config));

        $securityContext = $this->getMockBuilder("Symfony\Component\Security\Core\SecurityContextInterface")
            ->disableOriginalConstructor()
            ->getMock();

        $securityContext->expects($this->any())
            ->method('isGranted')
            ->will($this->returnValue(true));

        $container->expects($this->any())
            ->method('get')
            ->with('security.context')
            ->will($this->returnValue($securityContext));

        $controller = $this->buildController(array('container' => $container));

        $authFilter = $controller->getAuthorisationFilter();

        $this->assertNull($authFilter, 'Authorisation filter should be null as the user has role to skip auth filters');
    }

    public function testGetElasticSearchUrl()
    {
        $controller = $this->buildController();
        $url = $controller->getElasticSearchUrl('pretty=true', 'logs', '_search');

        $this->assertEquals('http://localhost:9200/logs/_search?pretty=true', $url, 'Should be url built from proxy');
    }

    public function testAddAuthFilterForRequestWithExistingFilter()
    {
        $controller = $this->buildController();

        // Authorisation filter
        $authFilter = array('term' => array('seller.id' => '123'));

        // Test with data having one filter where auth filter can be added
        $data = array(
            "query" => array(
                "filtered" => array(
                    "query" => array(
                        "term" => array("name.first" => "shay")
                    ),
                    "filter" => array(
                        "and" => array(
                            array(
                                "range" => array(
                                    "postDate" => array(
                                        "from" => "2010-03-01",
                                        "to" => "2010-04-01"
                                    )
                                )
                            ),
                            array(
                                "prefix" => array("name.second" => "ba")
                            )
                        )
                    )
                )
            )
        );

        $data = $controller->addAuthFilter($data, $authFilter);

        $this->assertEquals(
            '123',
            $data['query']['filtered']['filter']['and'][0]['term']['seller.id'],
            'Filter should get added correctly with an and filter'
        );

        $this->assertEquals(
            'ba',
            $data['query']['filtered']['filter']['and'][1]['and'][1]['prefix']['name.second'],
            'Filter should get added correctly with an and filter'
        );
    }

    public function testAddAuthFilterForRequestWithEmptyData()
    {
        $controller = $this->buildController();

        // Authorisation filter
        $authFilter = array('term' => array('seller.id' => '123'));

        // Test with empty data
        $data = array();
        $data = $controller->addAuthFilter($data, $authFilter);

        $this->assertEquals(
            '123',
            $data['query']['filtered']['filter']['term']['seller.id'],
            'Filter should get added correctly'
        );
    }

    public function testAddAuthFilterForRequestWithNoFilter()
    {
        $controller = $this->buildController();

        // Authorisation filter
        $authFilter = array('term' => array('seller.id' => '123'));

        // Test with data having only facets in request body
        $data = array(
            'facets' => array(
                'gross' => array(
                    'statistical' => array(
                        'field' => 'amount'
                    ),
                    'facet_filter' => array(
                        'query' => array(
                            'filtered' => array(
                                'query' => array(
                                    'match_all' => array()
                                ),
                                'filter' => array(
                                    'bool' => array()
                                )
                            )
                        )
                    )
                ),
                'fees' => array(
                    'statistical' => array(
                        'field' => 'fee'
                    ),
                    'facet_filter' => array(
                        'query' => array(
                            'filtered' => array(
                                'query' => array(
                                    'match_all' => array()
                                ),
                                'filter' => array(
                                    'bool' => array()
                                )
                            )
                        )
                    )
                )
            )
        );
        $data = $controller->addAuthFilter($data, $authFilter);

        $this->assertEquals(
            '123',
            $data['query']['filtered']['filter']['term']['seller.id'],
            'Filter should get added correctly'
        );
    }
}