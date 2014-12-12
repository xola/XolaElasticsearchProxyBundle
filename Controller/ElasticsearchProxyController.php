<?php
namespace Xola\ElasticsearchProxyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\Exception\RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Xola\ElasticsearchProxyBundle\ElasticSearchProxyAuthenticatorInterface;

class ElasticsearchProxyController extends Controller
{

    protected  $authenticator;

    public function proxyAction(Request $request, $index, $slug)
    {
        $user = $this->getUser();
        if (!$user) {
            // User not authenticated
            throw new UnauthorizedHttpException('');
        }

        // Check if requested elastic search index is allowed for querying
        $config = $this->container->getParameter('xola_elasticsearch_proxy');

        $this->setAuthenticator($config);
        $this->authenticator->authenticate($request, $index, $slug);

        if (!in_array($index, $config['client']['indexes'])) {
            throw new AccessDeniedHttpException();
        }

        // Get content for passing on in elastic search request
        $data = json_decode($request->getContent(), true);
        if (!$data) {
            throw new BadRequestHttpException();
        }

        $this->authenticator->filter($request, $index, $slug, $data);

        // Get url for elastic search
        $url = $this->getElasticSearchUrl($request->getQueryString(), $index, $slug);

        return $this->makeRequestToElasticsearch($url, $request->getMethod(), $data);
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
     * Injects authenticator from provided config
     * @param $config
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\RuntimeException
     */
    public function setAuthenticator($config)
    {
        if($this->authenticator) return;
        if($config instanceof ElasticSearchProxyAuthenticatorInterface) {
            $this->authenticator = $config;
            return;
        }

        $authenticatorInterface = 'Xola\ElasticsearchProxyBundle\ElasticSearchProxyAuthenticatorInterface';
        $authenticatorClass = $config['authenticator'];
        $this->authenticator = new $authenticatorClass();
        $reflectionClass = new \ReflectionClass($this->authenticator);

        if(!$reflectionClass->implementsInterface($authenticatorInterface)){
            $class = get_class($this->authenticator);
            throw new RuntimeException('Expected ' . $class . 'to implement ' . $authenticatorInterface);
        }
    }
}