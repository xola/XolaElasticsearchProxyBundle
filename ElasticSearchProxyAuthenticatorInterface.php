<?php

namespace Xola\ElasticsearchProxyBundle;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

interface ElasticSearchProxyAuthenticatorInterface
{
    /**
     * Throw an AccessDeniedException if access is denied to the user.
     *
     * @param Request $request
     * @param $index
     * @param $slug
     *
     * @throws AccessDeniedException
     */
    public function authenticate(Request $request, $index, $slug);

    /**
     * Add filter to restrict data
     *
     * @param Request $request
     * @param         $index
     * @param         $slug
     * @param array   $data
     *
     */
    public function filter(Request $request, $index, $slug, $data);
} 