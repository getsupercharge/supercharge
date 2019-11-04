## Installation

```bash
composer require supercharge/supercharge
```

You can now set your Supercharge URL by setting the `SUPERCHARGE_URL` environment variable in your `.env` file:

```dotenv
SUPERCHARGE_URL=https://... # Set your Supercharge URL here
```

Remember that Supercharge is useful in production, not in development. You probably want to set `SUPERCHARGE_URL` in the `.env` file of your production environment.

## Advanced configuration

You can configure all the options by publishing the package config file in your project:

```bash
php artisan vendor:publish --provider="Supercharge\\ServiceProvider"
```

This will create a `config/supercharge.php` that you can customize.
