<?php
namespace Xola\ElasticsearchProxyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

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

        $user = $this->get('security.context')->getToken()->getUser();

        if(!$user) {
            throw new AccessDeniedException();
        }

        // get the filter of the data
        $newFilter = array('term' => array('seller.id' => $user->getId()));

        $this->addBoolFilter($data, $newFilter);

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

        if($response === false) {
            return new Response('', 404, array('Content-Type' => $contentType));
        } else {
            $response = new Response($response, $curlInfo['http_code'], array('Content-Type' => $curlInfo['content_type']));

            return $response;
        }
    }

    /**
     * Returns the value of the needle within the target array
     *
     * @param array $data
     * @param array $newFilter
     *
     * @return array|null
     */
    private function addBoolFilter(&$data, $newFilter)
    {
        $res = null;
        foreach($data as $key => $val) {

            if($key === 'filter') {
                if(!empty($data[$key]['bool'])) {
                    if(!is_array($data[$key]['bool']['must'])) {
                        $data[$key]['bool']['must'] = array();
                    }
                    array_push($data[$key]['bool']['must'], $newFilter);
                }

                break;
            } elseif(is_array($data[$key]))
                $this->addBoolFilter($data[$key], $newFilter);
        }

        return $data;
    }
}