<?php
namespace Xola\ElasticsearchProxyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class XolaElasticsearchProxyController extends Controller
{

    public function proxyAction(Request $request)
    {
        // Forbid every request but jquery's XHR

        if (!$request->isXmlHttpRequest()) {
            return new Response('', 400,
                array('Content-Type' => 'application/json'));
        }

        $data = json_decode($request->getContent(), true);

        $url = $this->container->getParameter('xola_url');
        $method = $request->getMethod();
//        $params = $request->request->get('params');
        $contentType = $request->request->get('contentType');

        if ($contentType == null) {
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
        foreach ($requestCookies as $cookieName => $cookieValue) {
            $cookieArray[] = "{$cookieName}={$cookieValue}";
        }

        if (count($cookieArray)) {
            $cookie_string = implode('; ', $cookieArray);
            curl_setopt($ch, CURLOPT_COOKIE, $cookie_string);
        }

        $response = curl_exec($ch);
        curl_close($ch);

//        list($headers, $response) = explode("\r\n\r\n", $response, 2);
//        preg_match_all('/Set-Cookie: (.*)\b/', $headers, $cookies);
//        $cookies = $cookies[1];

        if ($response === false) {
            return new Response('', 404, array('Content-Type' => $contentType));
        } else {
//            $response = new Response($response, 200,
//                array('Content-Type' => $contentType));
//            foreach ($cookies as $rawCookie) {
//                $cookie = Cookie::fromString($rawCookie);
//                $value = $cookie->getValue();
//                if (!empty($value)) {
//                    $value = str_replace(' ', '+', $value);
//                }
//                $customCookie = new Cookie($cookie->getName(), $value, $cookie->getExpiresTime() == null ? 0 : $cookie->getExpiresTime(), $cookie->getPath());
//                $response->headers->setCookie($customCookie);
//            }
            return $response;
        }
    }
}