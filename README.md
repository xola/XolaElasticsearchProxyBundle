XolaElasticsearchProxyBundle
============================

A Symfony2 plugin that acts as a proxy for Elasticsearch.


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

Events
------

There are a couple of events fired by the bundle controller that can help you. By listening to these events you can add any custom authentication or filtering logic you require.

1. `elasticsearch_proxy.before_elasticsearch_request` -
This event is fired before the request is sent to Elasticsearch. The listener will receive `ElasticsearchProxyEvent` as an argument containing the request, index, slug, and the query object. You may modify this query object and set it back on the event with `setQuery`. The updated request will then be sent on to Elasticsearch. 

2. `elasticsearch_proxy.after_elasticsearch_response` -
This event is fired after a response has been received from Elasticsearch. The listener will receive `ElasticsearchProxyEvent` as
argument containing the request, index, slug, query, and response objects. You may modify the response and set it back into the event. The updated response is then sent back to the client.
