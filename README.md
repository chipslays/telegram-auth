<h1 align="center">
    Telegram Auth 🔑
</h1>

<p align="center">
    <p align="center">
    <a href="https://packagist.org/packages/kilogram/auth"><img src="https://img.shields.io/packagist/v/kilogram/auth" alt="Latest Version"></a>
    <a href="https://packagist.org/packages/kilogram/auth"><img src="https://img.shields.io/packagist/php-v/kilogram/auth" alt="PHP Version"></a>
    <a href="https://github.com/chipslays/telegram-auth/blob/main/LICENSE"><img src="https://img.shields.io/github/license/chipslays/telegram-auth?1" alt="License"></a>
    <a href="https://github.com/chipslays/telegram-auth"><img src="https://img.shields.io/github/stars/chipslays/telegram-auth?style=social" alt="Stars"></a>
    <a href="https://packagist.org/packages/kilogram/auth"><img src="https://img.shields.io/packagist/dt/kilogram/auth" alt="Downloads"></a>
</p>
    Secure and simple validation library for Telegram <strong><a href="https://core.telegram.org/widgets/login">Login Widget</a></strong> and <strong><a href="https://core.telegram.org/bots/webapps">Web App</a></strong> (including <strong><a href="https://core.telegram.org/bots/webapps#validating-data-for-third-party-use">Third-Party</a></strong> validation support).
</p>

## Features

- ✅ **Telegram Login Widget** – validate payloads from the login widget (hash verification, timestamp check).
- ✅ **Telegram Web App** – authenticate users inside mini‑apps by verifying `initData`.
- ✅ **Third‑Party Use** – validate Telegram data for external services (without a bot token, using bot ID).
- ✅ **Simple API** – ready‑to‑use methods like `isValidLoginWidget()`, `validateWebApp()`, plus exceptions for error handling.
- ✅ **Secure by design** – uses cryptographically strong hashing (`hash_hmac`, `sodium`) to prevent data tampering.
- ✅ **PHP 8.2+** – modern, strictly typed code.

## Requirements

- [PHP](https://www.php.net/): `^8.2`
- [ext-hash](https://www.php.net/manual/en/book.hash.php): `*`
- [ext-sodium](https://www.php.net/manual/en/book.sodium.php): `*`

## Installation

```bash
composer require kilogram/auth
```

## Quick start

> [!NOTE]
> Usage examples are also available in the [examples](/examples) directory.

### Login Widget (simple)

Basic validation of the data received from the Telegram Login Widget. Checks the hash and timestamp, returns a boolean result.

```php
use Kilogram\Auth\Validator;

$data = $_GET;

$validator = new Validator($_ENV['TELEGRAM_BOT_TOKEN']);

if ($validator->isValidLoginWidget($data)) {
    echo "Authenticated. User ID: " . $data['id'];
} else {
    echo "Authentication failed";
}
```

### Login Widget (with exceptions)

A more robust approach that throws specific exceptions for invalid input (missing parameters) and validation failures (tampered data).

```php
use Kilogram\Auth\Validator;
use Kilogram\Auth\Exceptions\InvalidDataException;
use Kilogram\Auth\Exceptions\ValidationException;

$data = $_GET;

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

Verifies the `initData` string from a Telegram Web App. Returns `true` if the signature is valid and the data is fresh.

```php
use Kilogram\Auth\Validator;

$initData = $_POST['initData'];

$validator = new Validator($_ENV['TELEGRAM_BOT_TOKEN']);

if ($validator->isValidWebApp($initData)) {
    echo "Web App authenticated";
} else {
    echo "Invalid initData";
}
```

### Web App (with exceptions)

Same as above, but throws exceptions for malformed input or invalid signatures, giving you finer control over error handling.

```php
use Kilogram\Auth\Validator;
use Kilogram\Auth\Exceptions\InvalidDataException;
use Kilogram\Auth\Exceptions\ValidationException;

$initData = $_POST['initData'];

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

Validates data for third‑party services without using a bot token. Only the bot ID is required.

```php
use Kilogram\Auth\Validator;

$initData = $_POST['initData'];

if (Validator::isValidWebAppDataForThirdParty($initData, $botId)) {
    echo "Web App authenticated (Third-Party)!";
} else {
    echo "Invalid data";
}
```

### Web App Third-Party (with exceptions)

The same third‑party validation, but with exception-based error reporting.

```php
use Kilogram\Auth\Validator;
use Kilogram\Auth\Exceptions\ValidationException;

$initData = $_POST['initData'];

try {
    Validator::validateWebAppDataForThirdParty($initData, $botId);
    echo "Web App authorized!";
} catch (ValidationException $e) {
    echo "Authentication failed";
}
```

> [!TIP]
> **When to use simple vs exceptions?**  
> Use the **simple** methods (`isValid*`) when you only need a boolean result (e.g., in controllers, middleware, or conditional logic).  
> Use the **exception** methods (`validate*`) when you need granular error handling – they distinguish between **malformed input** (developer errors, e.g., missing parameters) and **invalid signatures** (security issues, e.g., tampered data).

## License

MIT
