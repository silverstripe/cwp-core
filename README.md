# CWP Core Module

[![CI](https://github.com/silverstripe/cwp-core/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/cwp-core/actions/workflows/ci.yml)
[![Silverstripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

## About this module
This module includes core configuration that integrates a Silverstripe CMS project with the underlying infrastructure of Silverstripe Cloud Platform CCL (formally Revera). Most NZ public sector projects will have this module included after installing the [silverstripe/recipe-ccl recipe module](https://github.com/silverstripe/recipe-ccl).

## Installation

```sh
composer require cwp/cwp-core
```

## Configuration
There are some settings that can be modified in this module, depending on requirements. These are listed below.

### XSS Protection
By default, sites using this module instruct newer browsers to protect against cross-site scripting (XSS) attacks. This is done using an HTTP header (X-XSS-Protection). More information on this header can be found on the [Mozilla Developer Network](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection) site. To disable this feature, add the following to your YML configuration:

```yaml
CWP\Core\Control\InitialisationMiddleware:
  xss_protection_enabled: false
```

### Egress Proxy settings
An egress proxy is enabled for all external requests made by Silverstripe CMS sites running on Silverstripe Cloud CCL. This means that by default, all HTTP requests made using `curl` or PHP's stream functions are routed via a proxy. In some cases this may not be desired (e.g. if you wish to communicate with localhost). By default, there are two exceptions to this proxy: `services.cwp.govt.nz` and `localhost`. These cover all standard platform use cases (e.g. searching via Solr).

You can disable the egress proxy entirely by adding the following YML configuration:

```yaml
CWP\Core\Control\InitialisationMiddleware:
  egress_proxy_default_enabled: false
```

You can also add to the list of domains to disable the proxy by adding the following YML configuration:

```yaml
CWP\Core\Control\InitialisationMiddleware:
  egress_proxy_exclude_domains:
    - example.com
```

## Contributing

### Translations

Translations of the natural language strings are managed through a third party translation interface, transifex.com. Newly added strings will be periodically uploaded there for translation, and any new translations will be merged back to the project source code.

Please use [https://www.transifex.com/projects/p/silverstripe-cwp-core](https://www.transifex.com/projects/p/silverstripe-cwp-core) to contribute translations, rather than sending pull requests with YAML files.
