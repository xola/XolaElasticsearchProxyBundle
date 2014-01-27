<?php
namespace Xola\ElasticsearchProxyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ElasticsearchProxyController extends Controller
{

    public function proxyAction(Request $request, $slug)
    {
        // Forbid every request but json requests
        $contentType = $request->headers->get('Content-Type');

        // TODO: Kibana doesn't send content type header. Commenting for now. Check later
        //        if(strpos($request->headers->get('Content-Type'), 'application/json') === false) {
        //            return new Response('', 400,
        //                array('Content-Type' => 'application/json'));
        //        }

        // Get content for passing on to the curl
        $data = json_decode($request->getContent(), true);

        // Get query string
        $query = $request->getQueryString();

        // Construct url
        $config = $this->container->getParameter('xola_elasticsearch_proxy');
        $url = $config['client']['host'] . ':' . $config['client']['port'] . '/' . $slug;

        if($query) {
            // Query string exists. Add it to the url
            $url .= '?' . $query;
        }

        // Method for curl request
        $method = $request->getMethod();


        // TODO: Discuss. Do we want to set the default content type assuming we support only ajax json requests ?
        // Content type for curl
        if($contentType) {
            $contentType = 'application/json';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        $requestCookies = $request->cookies->all();

        $cookieArray = array();
        foreach($requestCookies as $cookieName => $cookieValue) {
            $cookieArray[] = "{$cookieName}={$cookieValue}";
        }

        if(count($cookieArray)) {
            $cookie_string = implode('; ', $cookieArray);
            curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);
        }

        $response = curl_exec($ch);
        $curlInfo = curl_getinfo($ch);
        curl_close($ch);

        // TODO: set other headers of the curl response
        //        list($headers, $response) = explode("\r\n\r\n", $response, 2);
        //        $headers = explode("\r\n", $headers);
        //        preg_match_all('/Set-Cookie: (.*)\b/', $headers, $cookies);
        //        $cookies = $cookies[1];

        if($response === false) {
            return new Response('', 404, array('Content-Type' => $contentType));
        } else {
            $response = new Response($response, $curlInfo['http_code'], array('Content-Type' => $curlInfo['content_type']));

            // TODO: check if all the below is required.
            //            foreach($cookies as $rawCookie) {
            //                $cookie = Cookie::fromString($rawCookie);
            //                $value = $cookie->getValue();
            //                if(!empty($value)) {
            //                    $value = str_replace(' ', '+', $value);
            //                }
            //                $customCookie = new Cookie($cookie->getName(), $value, $cookie->getExpiresTime() == null ? 0 : $cookie->getExpiresTime(), $cookie->getPath());
            //                $response->headers->setCookie($customCookie);
            //            }
            return $response;
        }
    }
}