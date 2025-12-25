<?php

use Telegram\Auth\Validator;
use Telegram\Auth\Exceptions\ValidationException;

require __DIR__ . '/../vendor/autoload.php';

$validator = new Validator($_ENV['YOUR_BOT_TOKEN']);

// Example 1: Web App - simple check
$initData = $_POST['initData'];

if ($validator->isValidWebApp($initData)) {
    echo "Web App authenticated!";
} else {
    echo "Invalid data";
}

// Example 2: Web App - with exception handling
try {
    $validator->validateWebApp($initData);
    echo "Web App authorized!";
} catch (ValidationException $e) {
    echo "Authentication failed";
}
