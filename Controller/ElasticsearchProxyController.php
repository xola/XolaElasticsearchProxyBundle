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

        $data = $this->addAuthFilter($data, $this->getAuthorisationFilter());

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
        $config = $this->container->getParameter('xola_elasticsearch_proxy');
        if ($config && isset($config['roles_skip_auth_filter'])) {
            foreach ($config['roles_skip_auth_filter'] as $role) {
                if ($this->get('security.context')->isGranted($role)) {
                    // User has roles to skip authorisation filter. Return empty filter
                    return null;
                }
            }
        }

        $user = $this->getUser();
        // Authorisation filter is the filter on seller
        $authFilter = array('term' => array('seller.id' => $user->getId()));

        return $authFilter;
    }

    /**
     * Returns the url for elastic search proxy.
     *
     * @param string $queryStr Query string of the request made to proxy
     * @param string $index Elastic search index to which queries are being made
     * @param string $slug Final bit of the url following index in the request made to proxy
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
     * @param array $query Elastic search query array
     * @param array $authFilter Authorisation filter that is to be added inside the query
     *
     * @throws BadRequestHttpException
     * @return array
     */
    public function addAuthFilter($query, $authFilter)
    {
        if (!is_array($query)) {
            return;
        }

        if (isset($query['query'])) {
            // Query exists.
            if (isset($query['query']['filtered'])) {
                // This is already filtered query. Add authorizaton using add filter.
                if (isset($query['query']['filtered']['filter'])) {
                    // Filter has been specified.
                    $filter = $query['query']['filtered']['filter'];
                    $query['query']['filtered']['filter'] = array(
                        'and' => array(
                            $authFilter,
                            $filter
                        )
                    );

                } else {
                    // Authorisation filter could not be applied because a filter key should exist withing a filtered key.. Bad Request.
                    throw new BadRequestHttpException();
                }
            } else {
                // This is not a filtered query. Make it a filtered query.
                $q = $query['query'];
                $query['query'] = array(
                    'filtered' => array(
                        'filter' => $authFilter,
                        'query' => $q
                    )
                );
            }
        } else {
            // Query does not exist. Add a filtered query, with authentication filter only.
            $query['query'] = array(
                'filtered' => array(
                    'filter' => $authFilter
                )
            );
        }

        return $query;
    }
}