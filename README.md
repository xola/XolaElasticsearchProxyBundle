XolaElasticsearchProxyBundle
============================

An authorization proxy for elasticsearch


Installation
------------

With composer, add:

```json
{
    "repositories" : [
            {
                "type" : "vcs",
                "url" : "https://github.com/xola/XolaElasticsearchProxyBundle"
            }
        ]
},
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
        new Xola\GiftBundle\XolaGiftBundle(),
        //...
```
Configuration
-------------

```yaml
# app/config/config.yml
xola_elasticsearch_proxy:
    client:
        protocol: 'http'
        host: localhost
        port: 9200
        # allows queries to following indexes only.
        indexes: ['logs']
```

Routing
-------------

Update your routing

```yaml
# app/config/routing.yml
# Xola elasticsearch proxy
XolaElasticsearchProxyBundle:
    resource: "@XolaElasticsearchProxyBundle/Resources/config/routing.yml"
    prefix:   /
```
The url for elasticsearch proxy by default is `/elasticsearch` and allows all HTTP methods.

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



Credits
-------

[XolaElasticsearchProxy]: https://github.com/xola/XolaElasticsearchProxyBundle
[Xola]: http://xola.com/overview
