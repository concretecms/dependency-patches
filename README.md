[![Tests](https://github.com/mlocati/concretecms-dependency-patches/actions/workflows/tests.yml/badge.svg)](https://github.com/mlocati/concretecms-dependency-patches/actions/workflows/tests.yml)
# Dependency patches for concrete5 and Concrete CMS

concrete5 v8 and Concrete CMS v9+ use a lot of third party libraries, installed via Composer.

Internal changes in newer PHP versions require to upgrade some of those composer packages, but some of them are no more compatible with the PHP versions we support, or they haven't been fixed yet.

This `dependency-patches` project contains those required patches, so that concrete5 and Concrete CMS can still use them.


## How to use

The official releases of concrete5 and Concrete CMS that can be downloaded from https://www.concretecms.org/download already contain the patches included in `dependency-patches`.

If you use a composer-based concrete5/Concrete CMS installation, the `composer.json` file of your project needs these settings:

```json
{
  "require": {
    "concretecms/dependency-patches": "^1"
  },
  "config": {
    "allow-plugins": {
      "mlocati/composer-patcher": true
    },
    "audit": {
      "ignore": {
        "PKSA-w9tt-7782-78jx": "league/flysystem CVE-2026-102601: fixed by concretecms/dependency-patches",
        "PKSA-kvv6-36cr-fkzb": "twig/twig CVE-2026-46627: fixed by concretecms/dependency-patches",
        "PKSA-sjvz-tbbr-vwth": "twig/twig CVE-2026-46628: fixed by concretecms/dependency-patches",
        "PKSA-h8hf-ytnd-5t9q": "twig/twig CVE-2026-46633: fixed by concretecms/dependency-patches",
        "PKSA-21g2-dzjv-sky5": "twig/twig CVE-2026-46634: fixed by concretecms/dependency-patches",
        "PKSA-3mcc-k66d-pydb": "twig/twig CVE-2026-46638: fixed by concretecms/dependency-patches",
        "PKSA-wwb1-81rc-pd65": "twig/twig CVE-2026-47730: fixed by concretecms/dependency-patches"
      }
    }
  },
  "extra": {
    "allow-subpatches": [
      "concretecms/dependency-patches"
    ]
  }
}
```

- `require`: needed only for concrete5 before 8.5.13 and for Concrete CMS 9.0.x (later versions already require `dependency-patches`)
- `allow-plugins`: lets Composer run the plugin that applies the patches
- `allow-subpatches`: lets that plugin apply the patches defined by `dependency-patches`
- `audit`: see [Security advisories](#security-advisories)


## Security advisories

Some of the patches fix security vulnerabilities in packages that are no longer updated for the PHP versions we support.

Composer knows nothing about patches: it still considers the patched versions as vulnerable, so `composer audit` reports them and `composer update` may refuse to install them.

The `audit`.`ignore` setting listed above tells Composer to ignore the advisories fixed by `dependency-patches`: it must be in the `composer.json` file of your project (it doesn't work in the `composer.json` files of dependencies).

The patches are applied only to `league/flysystem` 1.1.10 and `twig/twig` 3.11.3: don't ignore these advisories if you install other versions of those packages.


## How to add a new patch

See [CONTRIBUTING.md](CONTRIBUTING.md).
