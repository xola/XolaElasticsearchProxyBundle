<?php

namespace Xola\ElasticsearchProxyBundle;

use Symfony\Component\HttpFoundation\Request;

class Authenticator implements ElasticSearchProxyAuthenticatorInterface
{
    public function authenticate(Request $request, $index, $slug)
    {

    }

    public function filter(Request $request, $index, $slug, $data)
    {

    }
} 