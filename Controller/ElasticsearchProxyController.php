<?php
namespace Xola\ElasticsearchProxyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ElasticsearchProxyController extends Controller
{

    public function proxyAction(Request $request, $index, $slug)
    {
        $user = $this->getUser();
        if (!$user) {
            // User not authenticated
            throw new UnauthorizedHttpException('');
        }

        // Check if requested elastic search index is allowed for querying
        $config = $this->container->getParameter('xola_elasticsearch_proxy');
        if (!in_array($index, $config['client']['indexes'])) {
            throw new AccessDeniedHttpException();
        }

        // Get content for passing on in elastic search request
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            throw new BadRequestHttpException();
        }

        $filterCounter = array('applied' => 0, 'notApplied' => 0);
        // Inject authorisation filter
        $this->addAuthFilter($data, $this->getAuthorisationFilter(), $filterCounter);
        if ($filterCounter['applied'] <= 0 || $filterCounter['notApplied'] > 0) {
            // Authorisation filter could not be applied. Bad Request.
            throw new BadRequestHttpException();
        }

        // Get url for elastic search
        $url = $this->getElasticSearchUrl($request->getQueryString(), $index, $slug);

        return $this->makeRequestToElasticsearch($url, $request->getMethod(), $data);
    }

    /**
     * Returns the authorisation filter that can be applied within a bool filter's MUST clause
     * It is currently a filter on seller's id.
     */
    public function getAuthorisationFilter()
    {
        $user = $this->getUser();
        // Authorisation filter is the filter on seller
        $authFilter = array('term' => array('seller.id' => $user->getId()));

        return $authFilter;
    }

    /**
     * Returns the url for elastic search proxy.
     *
     * @param string $queryStr Query string of the request made to proxy
     * @param string $index    Elastic search index to which queries are being made
     * @param string $slug     Final bit of the url following index in the request made to proxy
     *
     * @return string
     */
    public function getElasticSearchUrl($queryStr, $index, $slug)
    {
        $config = $this->container->getParameter('xola_elasticsearch_proxy');

        // Construct url for making elastic search request
        $url = $config['client']['protocol'] . '://' . $config['client']['host'] . ':' . $config['client']['port'] . '/' . $index . '/' . $slug;

        if ($queryStr) {
            // Query string exists. Add it to the url
            $url .= '?' . $queryStr;
        }

        return $url;
    }

    /**
     * Makes request to Elastic search and returns symfony response
     *
     * @param $url
     * @param $method
     * @param $data
     *
     * @return Response
     */
    public function makeRequestToElasticsearch($url, $method, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $response = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        if ($response === false) {
            return new Response('', 404);
        } else {
            return new Response($response, $curlInfo['http_code'], array('Content-Type' => 'application/json'));
        }
    }

    /**
     * Modifies the specified elastic search query by injecting it with the specified authorisation filter.
     * Looks for all the boolean filters in the query and adds authorisation filter within 'MUST' clause.
     * Recursively calls itself on child array values
     *
     *
     * @param array $query          Elastic search query array
     * @param array $authFilter     Authorisation filter that is to be added inside the query
     * @param array $filterCounter  No. of times the filter was applied/not. Has keys 'applied' and 'notApplied' in it.
     *                              For an applicable filter, if authorisation is added, 'applied' gets incremented.
     *                              Else 'notApplied' gets incremented.
     * @param int   $appliedFilter  Applicable only when called recursively. Incremented each time the authFilter gets applied.
     * @param bool  $isQuery        Flag to indicate if $query is an elastic search query field or a child array of it.
     */
    public function addAuthFilter(&$query, $authFilter, &$filterCounter, &$appliedFilter = 0, $isQuery = false)
    {
        if (!is_array($query)) return;

        // Set default values if null specified.
        if (!is_array($filterCounter)) {
            $filterCounter = array();
        }
        // Filter counter must have key 'applied'. No. of filters where authorisation was added. This should
        // ideally be positive to ensure that the query was added with authorisation filter at least once.
        if (!isset($filterCounter['applied'])) $filterCounter['applied'] = 0;

        // Filter counter must have key 'notApplied'. No. of filters where addition of authorisation was missed. This
        // should ideally be zero, i.e we have added authorisation on all filters where ever applicable
        if (!isset($filterCounter['notApplied'])) $filterCounter['notApplied'] = 0;

        foreach ($query as $key => $val) {

            if ($key === 'filter') {
                if ($isQuery) {
                    // This is filter within a query. Fine
                    if (isset($query[$key]['bool']) && is_array($query[$key]['bool'])) {
                        // Bool filter exists
                        if (!isset($query[$key]['bool']['must'])) {
                            $query[$key]['bool']['must'] = array();
                        }

                        if (is_array($query[$key]['bool']['must'])) {
                            array_push($query[$key]['bool']['must'], $authFilter);
                            $appliedFilter++;
                        }
                    }
                    // Else: This filter does not have 'bool' key in it. Right now we support only boolean filters.
                    // Will get rejected as we don't increase $filterCounter['applied'] count
                }
                // Else. This is a filter that is not within a query. Will get rejected as we don't increase
                // $filterCounter['applied'] count
            } else {

                if ($key === 'query' && !$isQuery) {
                    // This is a top level query field.

                    // Counter to check how many times filter was applied within this array
                    $applyFilterCount = 0;

                    $this->addAuthFilter($query[$key], $authFilter, $filterCounter, $applyFilterCount, true);

                    // This was a top level query field. Check if the Auth filter was added within this.
                    if ($applyFilterCount <= 0) {
                        // Filter was not applied to this query. Not right. This is a top level query and an
                        // authorisation filter was supposed to get added within it.
                        $filterCounter['notApplied'] += 1;
                    } else {
                        // Filter was successfully apply to this query
                        $filterCounter['applied'] += 1;
                    }
                } else {
                    $this->addAuthFilter($query[$key], $authFilter, $filterCounter, $appliedFilter, $isQuery);
                }
            }

        }
    }
}