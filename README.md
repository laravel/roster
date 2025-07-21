<p align="center"><img src="/art/logo.svg" alt="Logo Laravel Roster"></p>

# Laravel Roster

Which Laravel packages is a project using?

<p align="center">
<a href="https://github.com/laravel/roster/actions"><img src="https://github.com/laravel/roster/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/dt/laravel/roster" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/v/laravel/roster" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/roster"><img src="https://img.shields.io/packagist/l/laravel/roster" alt="License"></a>
</p>

> 5. Fill out the package long introduction in the readme
> 7. Replace the `art/logo.svg` with the new package logo
> 8. Replace the `public/favicon.ico` with the new package favicon (optional)
> 9. Remove this quote block from your readme

## Introduction

`composer install laravel/roster`

## Usage

```php
use Laravel\Roster\Roster;

$roster = Roster::scan($directory);

// Get all packages
$roster->packages();

// Get only packages that will be used in production
$roster->packages()->production();

// Packages that are only used for dev
$roster->packages()->dev();

// Check if a package is in use
$roster->uses(Packages::INERTIA);

// Check if a particular version of a package is in use
$roster->usesVersion(Packages::INERTIA, '2.0.0', '>=');
```

## Contributing

Thank you for considering contributing to Using! The contribution guide can be found in
the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by
the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

Please review [our security policy](https://github.com/laravel/envoy/security/policy) on how to report security
vulnerabilities.

## License

Laravel Using is open-sourced software licensed under the [MIT license](LICENSE.md).
