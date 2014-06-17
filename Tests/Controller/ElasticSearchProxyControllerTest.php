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

    public function testAddAuthFilter_singleValidFilter()
    {
        $controller = $this->buildController();

        // Authorisation filter
        $authFilter = array('term' => array('seller.id' => '123'));

        // Test with empty data
        $data = array();
        $filterCounter = array('applied' => 0, 'notApplied' => 0);
        $controller->addAuthFilter($data, $authFilter, $filterCounter);

        $this->assertEquals(
            0,
            $filterCounter['applied'],
            'Applied count should be zero as there were no applicable queries where filter can be applied'
        );
        $this->assertEquals(
            0,
            $filterCounter['notApplied'],
            'notApplied count should be zero as there were no applicable queries where filter can be applied in first place'
        );
        $this->assertCount(0, $data);

        // Test with data having one filter where auth filter can be added
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
                )
            )
        );
        $filterCounter = array('applied' => 0, 'notApplied' => 0);
        $controller->addAuthFilter($data, $authFilter, $filterCounter);

        $this->assertEquals(
            1,
            $filterCounter['applied'],
            'Applied count should be zero as there were no applicable queries where filter can be applied'
        );
        $this->assertEquals(
            0,
            $filterCounter['notApplied'],
            'notApplied count should be zero as there were no applicable queries where filter can be applied in first place'
        );
        $this->assertEquals(
            '123',
            $data['facets']['gross']['facet_filter']['query']['filtered']['filter']['bool']['must'][0]['term']['seller.id'],
            'Filter should get added correctly within must clause of bool filter'
        );
    }

    public function testAddAuthFilterEmptyFilter()
    {
        $controller = $this->buildController();

        // Authorisation filter
        $authFilter = array();

        // Test with empty data
        $data = array();
        $filterCounter = array('applied' => 0, 'notApplied' => 0);
        $controller->addAuthFilter($data, $authFilter, $filterCounter);

        $this->assertEquals(
            0,
            $filterCounter['applied'],
            'Applied count should be zero as there were no applicable queries where filter can be applied'
        );
        $this->assertEquals(
            0,
            $filterCounter['notApplied'],
            'notApplied count should be zero as there were no applicable queries where filter can be applied in first place'
        );
        $this->assertCount(0, $data);

        // Test with data having one filter where auth filter can be added
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
                )
            )
        );
        $filterCounter = array('applied' => 0, 'notApplied' => 0);
        $controller->addAuthFilter($data, $authFilter, $filterCounter);

        $this->assertEquals(
            1,
            $filterCounter['applied'],
            'Applied count should be zero as there were no applicable queries where filter can be applied'
        );
        $this->assertEquals(
            0,
            $filterCounter['notApplied'],
            'notApplied count should be zero as there were no applicable queries where filter can be applied in first place'
        );
        $this->assertEquals(
            '123',
            $data['facets']['gross']['facet_filter']['query']['filtered']['filter']['bool']['must'][0]['term']['seller.id'],
            'Filter should get added correctly within must clause of bool filter'
        );
    }

    public function testAddAuthFilter_singleInvalidFilter()
    {
        $controller = $this->buildController();

        // Authorisation filter
        $authFilter = array('term' => array('seller.id' => '123'));

        // Test with data having one filter where auth filter cannot be added. Right now we support only bool filters.
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
                                    'term' => array()
                                )
                            )
                        )
                    )
                )
            )
        );
        $filterCounter = array('applied' => 0, 'notApplied' => 0);
        $controller->addAuthFilter($data, $authFilter, $filterCounter);

        $this->assertEquals(
            0,
            $filterCounter['applied'],
            'Applied count should be zero as there is one applicable query where filter cannot be applied'
        );
        $this->assertEquals(
            1,
            $filterCounter['notApplied'],
            'notApplied count should be one as there is one applicable query where filter cannot be applied'
        );
        $this->assertArrayNotHasKey(
            'bool',
            $data['facets']['gross']['facet_filter']['query']['filtered']['filter'],
            'Filter should  not get added'
        );
    }

    public function testAddAuthFilter_multipleWithOneInvalidFilter()
    {
        $controller = $this->buildController();

        // Authorisation filter
        $authFilter = array('term' => array('seller.id' => '123'));

        // Test with data having many filters where auth filter cannot be added in one. Right now we support only bool filters.
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
                                    'term' => array()
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
        $filterCounter = array('applied' => 0, 'notApplied' => 0);
        $controller->addAuthFilter($data, $authFilter, $filterCounter);

        $this->assertEquals(
            1,
            $filterCounter['applied'],
            'Applied count should be zero as there is one applicable query where filter cannot be applied'
        );
        $this->assertEquals(
            1,
            $filterCounter['notApplied'],
            'notApplied count should be one as there is one applicable query where filter cannot be applied'
        );
        $this->assertArrayNotHasKey(
            'bool',
            $data['facets']['gross']['facet_filter']['query']['filtered']['filter'],
            'Filter should  not get added'
        );
    }

    public function testAddAuthFilter_multipleValidFilters()
    {
        $controller = $this->buildController();

        // Authorisation filter
        $authFilter = array('term' => array('seller.id' => '123'));

        // Test with data having many filters where auth filter cannot be added in all.
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
        $filterCounter = array('applied' => 0, 'notApplied' => 0);
        $controller->addAuthFilter($data, $authFilter, $filterCounter);

        $this->assertEquals(
            2,
            $filterCounter['applied'],
            'Applied count should be zero as there is one applicable query where filter cannot be applied'
        );
        $this->assertEquals(
            0,
            $filterCounter['notApplied'],
            'notApplied count should be one as there is one applicable query where filter cannot be applied'
        );
        $this->assertEquals(
            '123',
            $data['facets']['gross']['facet_filter']['query']['filtered']['filter']['bool']['must'][0]['term']['seller.id'],
            'Filter should get added correctly within must clause of bool filter'
        );
        $this->assertEquals(
            '123',
            $data['facets']['fees']['facet_filter']['query']['filtered']['filter']['bool']['must'][0]['term']['seller.id'],
            'Filter should get added correctly within must clause of bool filter'
        );
    }
}