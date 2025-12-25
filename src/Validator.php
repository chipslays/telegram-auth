<?php

declare(strict_types=1);

namespace Kilogram\Auth;

use RuntimeException;
use SensitiveParameter;
use Kilogram\Auth\Exceptions\InvalidDataException;
use Kilogram\Auth\Exceptions\ValidationException;

/**
 * Telegram Authentication Validator.
 *
 * Handles validation for both Login Widget and Web App data (+ Third-party).
 */
final readonly class Validator
{
    /**
     * Telegram public key for production environment (Ed25519)
     */
    public const PUBLIC_KEY_PROD = 'e7bf03a2fa4602af4580703d88dda5bb59f32ed8b02a56c187fe7d34caed242d';

    /**
     * Telegram public key for test environment (Ed25519)
     */
    public const PUBLIC_KEY_TEST = '40055058a4ee38156a06562e52eece92a771bcd8346a8c4615cb7376eddf72ec';

    /**
     * @param string $botToken Your Telegram Bot Token (sensitive data)
     *
     * @throws InvalidDataException If bot token is empty
     */
    public function __construct(
        #[SensitiveParameter] private string $botToken
    ) {
        if ($this->botToken === '') {
            throw InvalidDataException::emptyToken();
        }
    }

    /**
     * Validate Telegram Login Widget data.
     *
     * @param array<string, mixed> $data Data received from Telegram Login Widget
     * @return bool True if data is valid
     *
     * @throws InvalidDataException If data format is invalid
     * @throws ValidationException If hash validation fails
     */
    public function validateLoginWidget(array $data): bool
    {
        $hash = $data['hash'] ?? throw InvalidDataException::missingHash();

        if (!is_string($hash)) {
            throw InvalidDataException::invalidInitDataFormat();
        }

        $dataWithoutHash = array_diff_key($data, ['hash' => true]);

        $secretKey = hash(algo: 'sha256', data: $this->botToken, binary: true);

        return $this->verifyHash(
            data: $dataWithoutHash,
            secretKey: $secretKey,
            expectedHash: $hash
        );
    }

    /**
     * Validate Telegram Web App initData.
     *
     * @param string $initData Raw initData string from Telegram Web App
     * @return bool True if data is valid
     *
     * @throws InvalidDataException If initData format is invalid
     * @throws ValidationException If hash validation fails
     */
    public function validateWebApp(string $initData): bool
    {
        $parsedData = $this->parseInitData($initData);

        $hash = $parsedData['hash'] ?? throw InvalidDataException::missingHash();
        unset($parsedData['hash']);

        $secretKey = hash_hmac(
            algo: 'sha256',
            data: $this->botToken,
            key: 'WebAppData',
            binary: true
        );

        return $this->verifyHash(
            data: $parsedData,
            secretKey: $secretKey,
            expectedHash: $hash
        );
    }

    /**
     * Validate data for Third-Party use (static method).
     *
     * @param string $initData Raw initData string
     * @param int|string $botId The Bot ID
     * @param string $publicKeyHex Public key in HEX format (default: production)
     * @return bool
     *
     * @throws InvalidDataException If initData format is invalid
     * @throws ValidationException If hash validation fails
     */
    public static function validateWebAppDataForThirdParty(
        string $initData,
        int|string $botId,
        string $publicKeyHex = self::PUBLIC_KEY_PROD
    ): bool
    {
        $parsed = self::parseInitData($initData);

        $signature = $parsed['signature'] ?? throw InvalidDataException::missingSignature();
        unset($parsed['hash'], $parsed['signature']);

        $checkStringParts = ["$botId:WebAppData"];
        ksort($parsed);
        foreach ($parsed as $k => $v) {
            $checkStringParts[] = "$k=$v";
        }
        $checkString = implode("\n", $checkStringParts);

        $publicKey = hex2bin($publicKeyHex);

        $signatureBin = self::base64UrlDecode($signature);

        if (strlen($signatureBin) !== SODIUM_CRYPTO_SIGN_BYTES) {
            throw ValidationException::invalidSignature();
        }

        if (!sodium_crypto_sign_verify_detached($signatureBin, $checkString, $publicKey)) {
             throw ValidationException::invalidSignature();
        }

        return true;
    }

    /**
     * Check if login widget data is valid (safe boolean return).
     *
     * @param array<string, mixed> $data Data received from Telegram Login Widget
     */
    public function isValidLoginWidget(array $data): bool
    {
        try {
            return $this->validateLoginWidget($data);
        } catch (ValidationException | InvalidDataException) {
            return false;
        }
    }

    /**
     * Check if web app data is valid (safe boolean return).
     *
     * @param string $initData Raw initData string from Telegram Web App
     * @return bool True if data is valid
     */
    public function isValidWebApp(string $initData): bool
    {
        try {
            return $this->validateWebApp($initData);
        } catch (ValidationException | InvalidDataException) {
            return false;
        }
    }

    /**
     * Check if web app data is valid for third-party use (safe boolean return).
     *
     * @param string $initData Raw initData string
     * @param int|string $botId The Bot ID
     * @param string $publicKeyHex Public key in HEX format (default: production)
     * @return bool
     */
    public static function isValidWebAppDataForThirdParty(
        string $initData,
        int|string $botId,
        string $publicKeyHex = self::PUBLIC_KEY_PROD
    ): bool
    {
        try {
            return self::validateWebAppDataForThirdParty($initData, $botId, $publicKeyHex);
        } catch (ValidationException | InvalidDataException | \RuntimeException $e) {
            return false;
        }
    }

    /**
     * Parse and validate initData string.
     *
     * @return array<string, string> Parsed data
     *
     * @throws InvalidDataException If initData format is invalid
     */
    private static function parseInitData(string $initData): array
    {
        if ($initData === '') {
             throw InvalidDataException::invalidInitDataFormat();
        }

        parse_str($initData, $parsedData);

        if (!is_array($parsedData) || $parsedData === []) {
            throw InvalidDataException::invalidInitDataFormat();
        }

        return array_map(strval(...), $parsedData);
    }

    /**
     * Core validation logic used by both methods.
     *
     * @param array<string, mixed> $data
     */
    private function verifyHash(array $data, string $secretKey, string $expectedHash): bool
    {
        $checkString = $this->buildCheckString($data);

        $generatedHash = hash_hmac(
            algo: 'sha256',
            data: $checkString,
            key: $secretKey
        );

        if (!hash_equals($generatedHash, $expectedHash)) {
            throw ValidationException::invalidHash();
        }

        return true;
    }

    /**
     * Build check string from data array according to Telegram docs.
     *
     * @param array<string, mixed> $data
     */
    private function buildCheckString(array $data): string
    {
        ksort($data);

        $checkArray = [];
        foreach ($data as $key => $value) {
            $checkArray[] = "$key=" . (string)$value;
        }

        return implode("\n", $checkArray);
    }

    /**
     * Decodes a base64url-encoded string.
     *
     * @param string $data The string to decode.
     * @return string The decoded string.
     * @see https://tools.ietf.org/html/rfc4648#section-5
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(str_replace(['-','_'], ['+','/'], $data));
    }
}
