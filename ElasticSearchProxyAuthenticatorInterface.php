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
     * @return mixed
     *
     * @throws AccessDeniedException
     */
    public function authenticate(Request $request, $index, $slug);
} 