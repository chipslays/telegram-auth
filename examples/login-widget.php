<?php

use Telegram\Auth\Validator;
use Telegram\Auth\Exceptions\InvalidDataException;
use Telegram\Auth\Exceptions\ValidationException;

require __DIR__ . '/../vendor/autoload.php';

$validator = new Validator($_ENV['YOUR_BOT_TOKEN']);

// Example 1: Login Widget - simple check
$loginData = $_GET; // Data from Telegram Login Widget

if ($validator->isValidLoginWidget($loginData)) {
    echo "Authenticated! User ID: " . $loginData['id'];
} else {
    echo "Authentication failed";
}

// Example 2: Login Widget - with exception handling
try {
    $validator->validateLoginWidget($loginData);
    echo "Success! Welcome " . $loginData['first_name'];
} catch (InvalidDataException $e) {
    // Missing hash or wrong format
    echo "Bad request: " . $e->getMessage();
} catch (ValidationException $e) {
    // Invalid signature
    echo "Authentication failed";
}