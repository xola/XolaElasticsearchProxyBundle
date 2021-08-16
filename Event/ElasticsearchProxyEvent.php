<?php

namespace Xola\ElasticsearchProxyBundle\Event;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\Event as SymfonyEvent;

class ElasticsearchProxyEvent extends SymfonyEvent
{
    // The request object
    protected $request;

    protected $slug;

    // The elasticsearch index
    protected $index;

    // The elasticsearch query array
    protected $query;

    //Response as received from ElasticSearch
    protected $response;

    public function __construct(Request $request, $index, $slug, array &$query, Response $response = null)
    {
        $this->request = $request;
        $this->index = $index;
        $this->slug = $slug;
        $this->query = $query;
        $this->response = $response;
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function setIndex($index)
    {
        $this->index = $index;
    }

    public function getIndex()
    {
        return $this->index;
    }

    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param mixed $query
     */
    public function setQuery($query)
    {
        $this->query = $query;
    }

    /**
     * @return mixed
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * @param Response $response
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function getResponse(): ?Response
    {
        return $this->response;
    }
}
