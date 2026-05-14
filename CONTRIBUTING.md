# Contributing

Contributions are welcome and will be fully credited. We accept contributions via Pull Requests on [GitHub](https://github.com/NextMigrant/laravel-plunk).

## Pull Requests

- **Add tests!** - Your patch won't be accepted if it doesn't have tests.
- **Document any change in behavior** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.
- **Consider our release cycle** - We try to follow SemVer v2.0.0. Randomly breaking public APIs is not an option.
- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

## Setup

1. Fork the repository
2. Clone your fork: `git clone https://github.com/your-username/laravel-plunk.git`
3. Install dependencies: `composer install`
4. Run the tests to ensure everything is working: `composer test`

## Code Style

This package uses Laravel Pint for code styling. Please run the formatter before committing:

```bash
composer format
```

Our GitHub Actions will automatically check for code style issues and static analysis (PHPStan).

## Running Tests

We use Pest for testing.

```bash
composer test
```

## Security Vulnerabilities

If you discover any security related issues, please email `dev@nextmigrant.com` instead of using the issue tracker.

## License

By contributing to this repository, you agree that your contributions will be licensed under its MIT License.
