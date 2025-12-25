# Telegram Auth

Secure and simple validation library for Telegram **Login Widget** and **Web App** (including **Third-Party** validation support).

## Features

- Validate Telegram Login Widget payload.
- Validate Telegram Web App.
- Validate Telegram Web App data for Third-Party Use.

## Requirements

- [PHP](https://www.php.net/): `^8.2`
- [ext-hash](https://www.php.net/manual/en/book.hash.php): `*`
- [ext-sodium](https://www.php.net/manual/en/book.sodium.php): `*`

## Installation

```bash
composer require kilogram/auth
```

## Quick start

Usage examples are also available in the [examples](/examples) directory.

### Login Widget (simple)

```php
use Kilogram\Auth\Validator;

$validator = new Validator($_ENV['TELEGRAM_BOT_TOKEN']);

if ($validator->isValidLoginWidget($data)) {
    echo "Authenticated. User ID: " . $data['id'];
} else {
    echo "Authentication failed";
}
```

### Login Widget (with exceptions)

```php
use Kilogram\Auth\Validator;
use Kilogram\Auth\Exceptions\InvalidDataException;
use Kilogram\Auth\Exceptions\ValidationException;

$validator = new Validator($_ENV['TELEGRAM_BOT_TOKEN']);

try {
    $validator->validateLoginWidget($data);
    echo "Authenticated. Hello " . ($data['first_name'] ?? 'user');
} catch (InvalidDataException $e) {
    // Developer error: invalid input format (e.g. missing "hash")
    echo "Bad request: " . $e->getMessage();
} catch (ValidationException $e) {
    // Invalid signature: possible tampering
    echo "Authentication failed";
}
```

### Web App (simple)

```php
use Kilogram\Auth\Validator;

$validator = new Validator($_ENV['TELEGRAM_BOT_TOKEN']);

if ($validator->isValidWebApp($initData)) {
    echo "Web App authenticated";
} else {
    echo "Invalid initData";
}
```

### Web App (with exceptions)

```php
use Kilogram\Auth\Validator;
use Kilogram\Auth\Exceptions\InvalidDataException;
use Kilogram\Auth\Exceptions\ValidationException;

$validator = new Validator($_ENV['TELEGRAM_BOT_TOKEN']);

try {
    $validator->validateWebApp($initData);
    echo "Web App authenticated";
} catch (InvalidDataException $e) {
    // Developer error: initData format is broken / empty
    echo "Bad request: " . $e->getMessage();
} catch (ValidationException $e) {
    // Invalid signature
    echo "Authentication failed";
}
```


### Web App Third-Party (simple)

```php
use Kilogram\Auth\Validator;

if (Validator::isValidWebAppDataForThirdParty($initData, $botId)) {
    echo "Web App authenticated (Third-Party)!";
} else {
    echo "Invalid data";
}
```

### Web App Third-Party (with exceptions)

```php
use Kilogram\Auth\Validator;
use Kilogram\Auth\Exceptions\ValidationException;

try {
    Validator::validateWebAppDataForThirdParty($initData, $botId);
    echo "Web App authorized!";
} catch (ValidationException $e) {
    echo "Authentication failed";
}
```

## License

MIT
