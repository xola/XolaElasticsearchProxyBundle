<?php
namespace Xola\ElasticsearchProxyBundle\Tests\Mocks;

class MockUser
{
    protected $id;

    public function getId()
    {
        return $this->id;
    }
}