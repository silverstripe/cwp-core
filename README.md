# CWP Core Module

[![CI](https://github.com/silverstripe/cwp-core/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/cwp-core/actions/workflows/ci.yml)

## About this module
This module includes core configuration that integrates a Silverstripe CMS project with the underlying infrastructure of Silverstripe Cloud Platform CCL (formally Revera). Most NZ public sector projects will have this module included after installing the [silverstripe/recipe-ccl recipe module](https://github.com/silverstripe/recipe-ccl).

## Installation

```sh
composer require cwp/cwp-core
```

## Configuration

Every feature this module applies can be switched off from your project's YAML. Each feature owns its own flag, and `_config/features.yml` lists all of them in one place with their defaults. All flags default to `true`, so an existing project gets the same behaviour it always had.

To turn a feature off, set its flag to `false` in your project's YAML:

```yaml
CWP\Core\Config\SessionConfig:
  enabled: false
```

Turning a feature off never leaves the site half configured: the module either stops doing the thing, or puts the affected configuration back. Where the module layers onto a service another module defines, the value that other module declares is what it goes back to, rather than the setting disappearing.

A value your project has already set is left alone, so you can disable a feature and configure your own replacement side by side. This works by comparing against the value this module sets, which means a project that independently configures the same value the module already uses is treated as the module's own. Where that matters, set your value from `_config.php`, which is applied after the flags are read.

### Feature flags

| Feature | Flag | Disabling means |
| --- | --- | --- |
| Egress proxy, `X-XSS-Protection` and `Strict-Transport-Security` headers | `CWP\Core\Control\InitialisationMiddleware` | The middleware passes requests straight through. Egress proxy environment variables are not set, and neither header is added. |
| Basic authentication on test (UAT) environments | `CWP\Core\Control\CwpBasicAuthMiddleware` | The URL patterns this module protects on UAT stop applying, so there is no prompt on any route. `BasicAuth.entire_site_protected`, if your project sets it, still applies. Read the warning below before using this. |
| `$Content.RichLinks` link markers | `CWP\Core\Extension\RichLinksExtension` | `RichLinks()` returns the content unchanged, so templates calling it keep working without file size or external link markers. |
| `cwp` HTML editor configuration for CMS groups | `CWP\Core\Extension\CwpHtmlEditorConfig` | Groups without an explicit editor configuration fall back to the CMS default instead of `cwp`. |
| Login attempt notifications in the CMS | `CWP\Core\Extension\LoginAttemptNotifications` | No `X-LoginAttemptNotifications` header, so the CMS shows nothing about recent login attempts. |
| Atom output from `CwpAtomFeed` | `CWP\Core\Feed\CwpAtomFeed` | Feeds built with this class fall back to the RSS template, link tag and content type from `RSSFeed`. |
| CWP stylesheet in the CMS | `CWP\Core\Config\CmsStylesConfig` | `cwp/cwp-core:css/custom.css` is not loaded into the admin interface. Other entries in `extra_requirements_css` are untouched. |
| 24 minute session timeout, no CMS keepalive ping | `CWP\Core\Config\SessionConfig` | Sessions use the framework default of no timeout, and the CMS keeps the session alive while a window is open. |
| `autocomplete="off"` on password fields | `CWP\Core\Config\PasswordFieldConfig` | The browser decides whether to offer to fill password fields. |
| Account lockout and password change notifications | `CWP\Core\Config\MemberSecurityConfig` | Lockout returns to the framework default of 10 failed logins rather than 5. |
| `en_GB` site locale | `CWP\Core\Config\LocaleConfig` | The locale returns to the framework default of `en_US`, which changes date formatting and locale dropdowns. |
| NZISM password strength rules | `CWP\Core\Config\PasswordStrengthConfig` | Passwords are scored on entropy by the framework default validator, rather than requiring 10 characters across 3 of 4 character classes. |
| SHA-512 via PBKDF2 password hashing | `CWP\Core\Config\PasswordEncryptionConfig` | New and changed passwords are hashed with blowfish. Existing hashes keep working, because the algorithm is recorded per member. |
| HTTPS redirects for login and API URLs | `CWP\Core\Config\SslConfig` | Nothing redirects a plain HTTP request at the application layer. The platform still terminates TLS. |
| Errors sent to syslog and on to Graylog | `CWP\Core\Config\LoggingConfig` | The syslog handler is removed from the shared logger, so errors no longer reach Graylog. |
| oEmbed lookups through the egress proxy | `CWP\Core\Config\OEmbedConfig` | oEmbed lookups go direct, which fails on environments with no outbound access. |
| Doorman as the queued jobs runner | `CWP\Core\Config\QueuedJobsConfig` | The runner falls back to whatever `symbiote/silverstripe-queuedjobs` configures. |
| Text extraction from uploaded files | `CWP\Core\Config\TextExtractionConfig` | Files are no longer text extractable, and the extraction cache returns to that module's default. |
| Word document import on pages | `CWP\Core\Config\DocumentConverterConfig` | The document import is removed from the page edit form. |

### Settings within a feature

Some features have further settings beyond their flag. These are listed below.

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
