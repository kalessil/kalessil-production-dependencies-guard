# production-dependencies-guard

Prevents development packages from being added into `require` and getting into production environment. In practical field 
prevents e.g. debug tool-bars deployment into production environments.

Additionally, you can configure the guard to decline packages with missing/unfit license, abandoned or mentioning `debug` 
in description and analyze packages on basis of composer.lock (deeper analysis).

# Installation

`composer require --dev kalessil/production-dependencies-guard:dev-master`

# Configuration

Additional guard checks can be enabled in the top-level composer.json file:
```
{
    "name": "...",

    "extra": {
        "production-dependencies-guard": [
            "check-lock-file",
            "check-description",
            "check-license",
            "check-abandoned",
            "check-version"
            
            "white-list:vendor/package-one",
            "white-list:vendor/package-two",
            
            "accept-license:MIT",
            "accept-license:proprietary"
        ]
    }
}
```

- `white-list:...` adds a package to white-list, so it's not getting reported in spite of violations
- `check-lock-file` uses composer.lock instead of composer.json, allowing deeper dependencies analysis
- `check-description` enables description and keywords analysis (searches `debug`), allowing to detect custom dev-packages
- `check-abandoned` enables abandoned packages checking
- `check-license` enables license checking (packages must provide license information)
- `check-version` enables package version checking, and will reject any packages with a dev version (e.g. `dev-XXX as 1.2.3`)
- `accept-license:...` specifies which licenses should be accepted (if the setting omitted, any license incl. proprietary)

# Usage

When the package is added to require-dev section of your `composer.json` file (`"kalessil/production-dependencies-guard": "dev-master"`),
it'll **prevent adding dev-packages into `require` section**. Since dev-packages has no security guaranties 
(not intended for production use, only development purposes), this also improves your application security.

```
composer require --dev kalessil/production-dependencies-guard:dev-master

composer require phpunit/phpunit:*
# it should be `composer require --dev phpunit/phpunit:*` here
```

will run with an error (profit!):

```
./composer.json has been updated

Installation failed, reverting ./composer.json to its original content.

[RuntimeException]                                                                   
  Dependencies guard has found violations in require-dependencies (source: manifest):  
   - phpunit/phpunit: dev-package-name
```

# Stability

This package is only available in its `dev-master` version: according to the package purpose.