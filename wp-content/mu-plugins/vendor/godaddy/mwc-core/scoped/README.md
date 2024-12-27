# Prefixed external packages

This folder holds files from external packages re-namespaced (prefixed) to avoid conflicts with other plugins when running within WP. This is its own composer package.

It is not necessary to run any commands in this folder to develop mwc-core.

## How it works

php-scoper plus all the packages we want to re-namespace are required as dev dependencies in this folder's `composer.json`.

There is a php-scoper folder with finder files that dictate how/where to find the original vendored files.

There are composer scripts that run the php-scoper commands to copy files from this folder's vendor into the `packages` folder and prefix their namespaces.

The main `composer.json` autoloads this `packages` folder as psr-4 under `GoDaddy\WordPress\MWC\Core\Vendor\`.

Note: the commands below are meant to run while in this folder.

## Update re-namespaced packages

Update a specific package's version:
```
composer update firebase/php-jwt:^6.8.1
```

Then run the prefix-deps script. This runs the php-scoper commands that'll copy files from the vendor folder to the `packages` dir.
```
composer prefix-deps
```

Commit the changed/added files (if any):
```
git add .
git commit
```

Note: pre-commit will lint any changed files from the external libraries to match our code style rules. That's ok.

Cleanup: remove the vendor folder.
```
rm -r vendor
```

## Add a package

Add the package via composer as a `--dev` dependency:
```
composer require some/new-package:^1.2.3 --dev
```

Add a finder file under `php-scoper/` named after the new package, like `php-scoper/new-package.inc.php`, that finds all the files that we want to copy from the new package.
- Use an existing finder file from `php-scoper/` as a template.
- Be sure to include the new package's license file in the finder.

Add a script to this folder's `composer.json`.
```
        "prefix-new-package": [
            "@php ./vendor/bin/php-scoper add-prefix --prefix='GoDaddy\\WordPress\\MWC\\Core\\Vendor' --output-dir=./packages/some/new-package --config=php-scoper/php-jwt.inc.php --force --quiet",
            "echo 'some/new-package package has been prefixed! Please commit changes in the packages/ folder."
        ]
```

Then add that to the prefix-deps script.
```diff
        "prefix-deps": [
+           "composer prefix-new-package",
            "composer prefix-php-jwt"
        ],
```

Now run it.
```
composer prefix-deps
```

Commit the changed/added files (if any):
```
git add .
git commit
```

Note: pre-commit will lint any changed files from the external libraries to match our code style rules. That's ok.

Cleanup: remove the vendor folder.
```
rm -r vendor
```

In mwc-core's main `composer.json` file, add the new package under autoload:

```diff
     "autoload": {
         "psr-4": {
             "GoDaddy\\WordPress\\MWC\\Core\\": "src/",
+            "GoDaddy\\WordPress\\MWC\\Core\\Vendor\\Some\\NewPackage\\": "scoped/packages/some/new-package/src",
             "GoDaddy\\WordPress\\MWC\\Core\\Vendor\\Firebase\\JWT\\": "scoped/packages/firebase/php-jwt/src"
         }
     },
```

Commit the composer.json changes:
```
git add -p ../composer.json
git commit
```

That's all.

⚠️ If a package has its own dependencies, you'll likely have to follow these steps for each of those secondary dependencies'. We haven't tried it as of this writing. Prefer packages without dependencies or with a small, shallow dependency tree.
