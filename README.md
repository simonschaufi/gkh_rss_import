# TYPO3 extension `gkh_rss_import`

[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.me/simonschaufi/19.99)
[![Latest Stable Version](https://poser.pugx.org/gkh/gkh-rss-import/v/stable)](https://packagist.org/packages/gkh/gkh-rss-import)
[![Total Downloads](https://poser.pugx.org/gkh/gkh-rss-import/downloads)](https://packagist.org/packages/gkh/gkh-rss-import)
[![License](https://poser.pugx.org/gkh/gkh-rss-import/license)](https://packagist.org/packages/gkh/gkh-rss-import)
[![TYPO3](https://img.shields.io/badge/TYPO3-9.5.0-orange.svg)](https://get.typo3.org/version/9)
[![TYPO3](https://img.shields.io/badge/TYPO3-10.4.0-orange.svg)](https://get.typo3.org/version/10)

Import an RSS feed and show the content on a page.

## Features

* Frontend plugin, implementing best practices from TYPO3 CMS
* Supports editors & authors by providing
    * Lots of plugin options for flexible output rendering
    * Local caching of external feed content
    * Marker based template
* [Comprehensive documentation][1]

## Usage

### Installation

#### Installation using Composer

The recommended way to install the extension is using [Composer][2].

Run the following command within your Composer based TYPO3 project:

```bash
composer require gkh/gkh-rss-import
```

#### Installation as extension from TYPO3 Extension Repository (TER)

Download and install the [extension][3] with the extension manager module in the TYPO3 backend.

## Usage

- Add the TypoScript configuration in the Template module
- Add the Plugin on a page and configure it in the plugin settings right in the content element

## Administration corner

### Versions and support

| Branch      | TYPO3      | PHP       | Support / Development                   |
| ----------- | ---------- | ----------|---------------------------------------- |
| dev-master  | 9.5 - 10.x | 7.0 - 7.4 | unstable development branch             |
| 6.x         | 8.7 - 8.7  | 7.0 - 7.4 | bugfixes, security updates              |

### Changelog

Please look into the [official extension documentation in changelog chapter][4].

### Release Management

We follow [**semantic versioning**][5], which means, that
* **bugfix updates** (e.g. 1.0.0 => 1.0.1) just includes small bugfixes or security relevant stuff without breaking changes,
* **minor updates** (e.g. 1.0.0 => 1.1.0) includes new features and smaller tasks without breaking changes,
* and **major updates** (e.g. 1.0.0 => 2.0.0) breaking changes wich can be refactorings, features or bugfixes.

### Contribution

**Pull Requests** are gladly welcome! Please don't forget to add an issue and connect it to your pull requests. This
is very helpful to understand what kind of issue the **PR** is going to solve.

Bugfixes: Please describe what kind of bug your fix solve and give us feedback how to reproduce the issue. We're going
to accept only bugfixes if we can reproduce the issue.

[1]: https://docs.typo3.org/typo3cms/extensions/gkh_rss_import/
[2]: https://getcomposer.org/
[3]: https://extensions.typo3.org/extension/gkh_rss_import
[4]: https://docs.typo3.org/p/simonschauif/gkh_rss_import/master/en-us/Changelog/Index.html
[5]: https://semver.org/
