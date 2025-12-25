<?php

use Telegram\Auth\Validator;
use Telegram\Auth\Exceptions\ValidationException;

require __DIR__ . '/../vendor/autoload.php';

$initData = $_POST['initData'];
$botId = 12345678; // ID of the bot that opened the Web App

// Example 1: Third-Party - simple check
if (Validator::isValidWebAppDataForThirdParty($initData, $botId)) {
    echo "Web App authenticated (Third-Party)!";
} else {
    echo "Invalid data";
}

// Example 2: Third-Party - with exception handling
try {
    Validator::validateWebAppDataForThirdParty($initData, $botId);
    echo "Web App authorized!";
} catch (ValidationException $e) {
    echo "Authentication failed";
}
