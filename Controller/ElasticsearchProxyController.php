<?php
namespace Xola\ElasticsearchProxyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Xola\ElasticsearchProxyBundle\Event\ElasticsearchProxyEvent;

class ElasticsearchProxyController extends AbstractController
{
    public function proxyAction(Request $request, $index, $slug): ?Response
    {
        // Check if requested elastic search index is allowed for querying
        $config = $this->container->get('xola_elasticsearch_proxy');
        if (!in_array($index, $config['client']['indexes'])) {
            throw new AccessDeniedHttpException();
        }

        // Get content for passing on in elastic search request
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            // This is not an array possibly because there was invalid data in the request
            // Set it to an array since the proxy event expects one
            $data = array();
        }

        $event = new ElasticsearchProxyEvent($request, $index, $slug, $data);
        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch('elasticsearch_proxy.before_elasticsearch_request', $event);
        $data = $event->getQuery();
        // Get url for elastic search
        $url = $this->getElasticSearchUrl($request->getQueryString(), $index, $slug);
        $response = $this->makeRequestToElasticsearch($url, $request->getMethod(), $data);
        $event->setResponse($response);
        $dispatcher->dispatch('elasticsearch_proxy.after_elasticsearch_response', $event);

        return $event->getResponse();
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
    public function getElasticSearchUrl($queryStr, $index, $slug): string
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
    public function makeRequestToElasticsearch($url, $method, $data): Response
    {
        $ch = curl_init();

        // Strip query params from URL because ES doesn't like it
        $cleanUrl = strtok($url, '?');
        // This hack is required because json_encode converts [] to {} and ES does not like that
        $jsonValue = str_replace('"reverse_nested":[]', '"reverse_nested":{}', json_encode($data));

        curl_setopt($ch, CURLOPT_URL, $cleanUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonValue);
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
}
