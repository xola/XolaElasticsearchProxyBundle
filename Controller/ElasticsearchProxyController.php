<?php
namespace Xola\ElasticsearchProxyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class ElasticsearchProxyController extends Controller
{

    private $filterApplied = false;

    public function proxyAction(Request $request, $slug)
    {
        // Get content for passing on in elastic search request
        $data = json_decode($request->getContent(), true);

        $user = $this->get('security.context')->getToken()->getUser();

        if (!$user) {
            // User not authenticated
            throw new UnauthorizedHttpException('');
        }

        // Authorisation filter is the filter on seller
        $authFilter = array('term' => array('seller.id' => $user->getId()));

        // Inject authorisation filter
        $this->addAuthFilter($data, $authFilter);

        if (!$this->filterApplied) {
            // Authorisation filter could not be applied. Bad Request.
            throw new BadRequestHttpException();
        }

        // Get query string
        $query = $request->getQueryString();

        // TODO: Do we want to add the protocol to the url ?
        // Construct url for making elastic search request
        $config = $this->container->getParameter('xola_elasticsearch_proxy');
        $url = $config['client']['host'] . ':' . $config['client']['port'] . '/' . $slug;

        if ($query) {
            // Query string exists. Add it to the url
            $url .= '?' . $query;
        }

        // Method for elastic search request is same is the request method
        $method = $request->getMethod();

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
            return new JsonResponse('', 404);
        } else {
            return new JsonResponse($response, $curlInfo['http_code']);
        }
    }

    /**
     * Modifies the specified elastic search query by injecting it the specified authorisation filter.
     * Looks for all the boolean filters in the query and adds authorisation filter within 'MUST' clause.
     * Additionally sets the class variable 'filterApplied = TRUE' if the query is modified
     *
     *
     * @param array $query
     * @param array $authFilter
     *
     * @return array|null
     */
    private function addAuthFilter(&$query, $authFilter)
    {
        $res = null;
        foreach ($query as $key => $val) {

            if ($key === 'filter') {
                if (!empty($query[$key]['bool'])) {
                    if (!is_array($query[$key]['bool']['must'])) {
                        $query[$key]['bool']['must'] = array();
                    }
                    array_push($query[$key]['bool']['must'], $authFilter);

                    $this->filterApplied = true;
                }

            } elseif (is_array($query[$key]))
                $this->addAuthFilter($query[$key], $authFilter);
        }
    }
}