# CWP Core Module

[![Build Status](https://travis-ci.org/silverstripe/cwp-core.svg?branch=master)](https://travis-ci.org/silverstripe/cwp-core)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/silverstripe/cwp-core/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/silverstripe/cwp-core/?branch=master)
[![codecov](https://codecov.io/gh/silverstripe/cwp-core/branch/master/graph/badge.svg)](https://codecov.io/gh/silverstripe/cwp-core)

## About this module
This module includes core configuration that is required for Common Web Platform sites to function correctly.

### Configuration
There are some settings that can be modified in this module, depending on requirements. These are listed below.

#### XSS Protection
By default, CWP sites instruct newer browsers to protect against cross-site scripting (XSS) attacks. This is done using an HTTP header (X-XSS-Protection). More information on this header can be found on the [Mozilla Developer Network](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/X-XSS-Protection) site. To disable this feature, add the following to your YML configuration:
```
CWP\Core\Control\InitialisationMiddleware:
  xss_protection_enabled: false
```

#### Egress Proxy settings
CWP includes an egress proxy for all external requests made by SilverStripe sites running on CWP. This means that by default, all HTTP requests made using `curl` or PHP's stream functions are routed via a proxy. In some cases this may not be desired (e.g. if you wish to communicate with localhost). By default, there are two exceptions to this proxy: `services.cwp.govt.nz` and `localhost`. These cover all standard CWP use cases (e.g. searching via Solr).

You can disable the egress proxy entirely by adding the following YML configuration:
```
CWP\Core\Control\InitialisationMiddleware:
  egress_proxy_default_enabled: false
```

You can also add to the list of domains to disable the proxy by adding the following YML configuration:
```
CWP\Core\Control\InitialisationMiddleware:
  egress_proxy_exclude_domains:
    - example.com
```

## Contributing

### Translations

Translations of the natural language strings are managed through a third party translation interface, transifex.com. Newly added strings will be periodically uploaded there for translation, and any new translations will be merged back to the project source code.

Please use [https://www.transifex.com/projects/p/silverstripe-cwp-core](https://www.transifex.com/projects/p/silverstripe-cwp-core) to contribute translations, rather than sending pull requests with YAML files.
