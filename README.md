XolaElasticsearchProxyBundle
============================

A Symfony2 plugin that acts as an authorization proxy for elasticsearch.


Installation
------------

With composer, add:

```json
{
    "require": {
        "xola/elasticsearch-proxy-bundle" : "dev-master"
    }
}
```

Then enable it in your kernel:

```php
// app/AppKernel.php
public function registerBundles()
{
    $bundles = array(
        //...
        new Xola\ElasticsearchProxyBundle\XolaElasticsearchProxyBundle(),
        //...
```

Configuration
-------------

```yaml
# app/config/config.yml
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

Update your routing

```yaml
# app/config/routing.yml
# Xola elasticsearch proxy
XolaElasticsearchProxyBundle:
    resource: "@XolaElasticsearchProxyBundle/Resources/config/routing.yml"
    prefix:   /
```

The default path is `/elasticsearch` and permits all HTTP methods (GET, PUT, POST, etc.).

Override it. Ensure `index` (to capture elastic search index) and `slug` (to capture rest of the url) remain in the
route pattern.

```yaml
# app/config/routing.yml
xola_elasticsearch_proxy:
     pattern:  /myproxy/{index}/{slug}
     defaults: { _controller: XolaElasticsearchProxyBundle:ElasticsearchProxy:proxy }
     requirements:
        slug: ".+"
```

Event
-----
Dispatches event `elasticsearch_proxy.before_request`. This is the chance to modify the request being sent to elastic
search. The listener will receive `ElasticsearchProxyEvent` as argument. This event object contains request, index,
slug, and the query object. Modify this query object and set it back on the event with `setQuery`. The request will be
sent with modified body.