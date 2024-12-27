# GoDaddy WordPress Styles

Adding GoDaddy look and style to default WordPress components.

## How to use it

1. Install dependencies
```
npm i
```

2. Compile the styles
```
npm run build
```

## Publishing to [Packagist](https://packagist.org/packages/godaddy-wordpress/styles)

1. Bump versions in `package.json`, `StylesLoader.php`, and `godaddy-styles.php`.
2. Run `composer update` to update the `composer.lock` file.
3. Validate `composer.json` with `composer validate`.
4. Push updates and [publish a new release](https://github.com/godaddy-wordpress/styles/releases/new) in GitHub.