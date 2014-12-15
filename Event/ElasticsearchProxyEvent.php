<?php

namespace Xola\ElasticsearchProxyBundle\Event;
use Symfony\Component\EventDispatcher\Event as SymfonyEvent;
use Symfony\Component\HttpFoundation\Request;

class ElasticsearchProxyEvent extends SymfonyEvent {

    // The request object
    protected $request;

    protected $slug;

    // The elasticsearch index
    protected $index;

    // The elasticsearch query array
    protected $query;

    public function __construct(Request $request, $index, $slug, array &$query)
    {
        $this->request = $request;
        $this->index = $index;
        $this->slug = $slug;
        $this->query = $query;
    }

    /**
     * @param mixed $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return mixed
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param mixed $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * @return mixed
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param mixed $slug
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
    }

    /**
     * @return mixed
     */
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


}