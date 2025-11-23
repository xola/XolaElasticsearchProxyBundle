XolaElasticsearchProxyBundle
============================

A Symfony 5.4+ bundle that acts as an authorization proxy for Elasticsearch. It restricts access to configured indices and lets you intercept both the outgoing query and incoming response via events for custom filtering/auth logic.


Installation
------------

Require the bundle in your project (Symfony 5.4 / PHP 7.4+):

```bash
composer require xola/elasticsearch-proxy-bundle:dev-master
```

Register the bundle (Symfony 5 uses `config/bundles.php`, not `AppKernel`):

```php
// config/bundles.php
return [
    // ... other bundles ...
    Xola\ElasticsearchProxyBundle\XolaElasticsearchProxyBundle::class => ['all' => true],
];
```

Configuration
-------------

In `config/packages/xola_elasticsearch_proxy.yaml` (create if missing):

```yaml
xola_elasticsearch_proxy:
    client:
        protocol: http
        host: localhost
        port: 9200
        indexes: ['logs']
```

The `indexes` parameter lets you grant access to only the specified elasticsearch indexes.

Routing
-------

Include the bundle routes (Symfony 5, `config/routes/xola_elasticsearch_proxy.yaml`):

```yaml
XolaElasticsearchProxyBundle:
    resource: "@XolaElasticsearchProxyBundle/Resources/config/routing.yml"
    prefix: /
```

The default endpoint pattern is `/elasticsearch/{index}/{slug}` and permits all HTTP methods (GET, PUT, POST, etc.).

To override the path while retaining required placeholders (`index`, `slug`):

```yaml
# config/routes/xola_elasticsearch_proxy_override.yaml
my_elasticsearch_proxy:
    path: /myproxy/{index}/{slug}
    defaults: { _controller: 'Xola\\ElasticsearchProxyBundle\\Controller\\ElasticsearchProxyController::proxyAction' }
    requirements:
        slug: ".+"
```

Events
------

Two events are dispatched by the controller. You can register listeners/subscribers to implement auth, query shaping, or response filtering.

1. `elasticsearch_proxy.before_elasticsearch_request` – dispatched *before* sending the query to Elasticsearch. The event gives you: request, index, slug, and the mutable query array (`getQuery()` / `setQuery()`).
2. `elasticsearch_proxy.after_elasticsearch_response` – dispatched *after* receiving the response. Provides request, index, slug, original query, and the response (`getResponse()` / `setResponse()`).

In Symfony 5.4 the dispatcher signature is `dispatch(object $event, string $eventName)`. The event class no longer extends the deprecated `Event` base class – it's a plain PHP object.

Example listener service definition:

```php
// src/EventListener/ElasticsearchProxyListener.php
namespace App\EventListener;

use Xola\ElasticsearchProxyBundle\Event\ElasticsearchProxyEvent;

class ElasticsearchProxyListener
{
    public function before(ElasticsearchProxyEvent $event): void
    {
        $query = $event->getQuery();
        // mutate query (e.g. enforce filters)
        $query['size'] = min($query['size'] ?? 25, 100);
        $event->setQuery($query);
    }

    public function after(ElasticsearchProxyEvent $event): void
    {
        // Optionally transform response JSON
        $response = $event->getResponse();
        // ... modify $response if needed ...
        if ($response) {
            $event->setResponse($response);
        }
    }
}
```

```yaml
# config/services.yaml
services:
  App\EventListener\ElasticsearchProxyListener:
    tags:
      - { name: kernel.event_listener, event: elasticsearch_proxy.before_elasticsearch_request, method: before }
      - { name: kernel.event_listener, event: elasticsearch_proxy.after_elasticsearch_response, method: after }
```

Compatibility
-------------

This README reflects the upgrade to Symfony 5.4 and PHP 7.4+. If you need legacy Symfony (<4) setup instructions, refer to an earlier git tag or commit history.

License
-------

MIT
