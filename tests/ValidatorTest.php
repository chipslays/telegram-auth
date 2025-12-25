<?php

namespace Kilogram\Auth\Tests;

use PHPUnit\Framework\TestCase;
use Kilogram\Auth\Validator;
use Kilogram\Auth\Exceptions\InvalidDataException;
use Kilogram\Auth\Exceptions\ValidationException;

class ValidatorTest extends TestCase
{
    private string $botToken = '133333337:HUI-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxyu';
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator($this->botToken);
    }

    public function testConstructWithEmptyToken(): void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('Bot token cannot be empty');
        new Validator('');
    }

    // --- Login Widget Tests ---

    public function testValidateLoginWidgetSuccess(): void
    {
        // 1. Simulate data from Telegram Login Widget
        $data = [
            'id' => '123456789',
            'first_name' => 'John',
            'username' => 'johndoe',
            'auth_date' => (string) time(),
        ];

        // 2. Generate valid hash manually (as Telegram does)
        $dataCheckArr = [];
        foreach ($data as $key => $value) {
            $dataCheckArr[] = $key . '=' . $value;
        }
        sort($dataCheckArr);
        $checkString = implode("\n", $dataCheckArr);

        $secretKey = hash('sha256', $this->botToken, true);
        $hash = hash_hmac('sha256', $checkString, $secretKey);

        $data['hash'] = $hash;

        // 3. Assert validation passes
        $this->assertTrue($this->validator->validateLoginWidget($data));
        $this->assertTrue($this->validator->isValidLoginWidget($data));
    }

    public function testValidateLoginWidgetMissingHash(): void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('Hash parameter is required');

        $this->validator->validateLoginWidget(['id' => '123']);
    }

    public function testValidateLoginWidgetInvalidHash(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Invalid hash');

        $data = [
            'id' => '123',
            'hash' => 'fake_hash'
        ];

        $this->validator->validateLoginWidget($data);
    }

    public function testIsValidLoginWidgetReturnsFalseOnFailure(): void
    {
        $data = ['id' => '123', 'hash' => 'bad_hash'];
        $this->assertFalse($this->validator->isValidLoginWidget($data));
    }

    // --- Web App Tests ---

    public function testValidateWebAppSuccess(): void
    {
        // 1. Prepare data
        $params = [
            'query_id' => 'AAGL...',
            'user' => '{"id":123,"first_name":"John"}',
            'auth_date' => (string) time(),
        ];

        // 2. Generate valid hash (Web App logic: secret key = HMAC("WebAppData", token))
        $dataCheckArr = [];
        foreach ($params as $key => $value) {
            $dataCheckArr[] = $key . '=' . $value;
        }
        sort($dataCheckArr);
        $checkString = implode("\n", $dataCheckArr);

        $secretKey = hash_hmac('sha256', $this->botToken, 'WebAppData', true);
        $hash = hash_hmac('sha256', $checkString, $secretKey);

        // 3. Build initData string
        $params['hash'] = $hash;
        $initData = http_build_query($params);

        // 4. Assert
        $this->assertTrue($this->validator->validateWebApp($initData));
        $this->assertTrue($this->validator->isValidWebApp($initData));
    }

    public function testValidateWebAppInvalidFormat(): void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('Invalid initData format');

        // Passing array instead of string query
        $this->validator->validateWebApp("");
    }

    public function testValidateWebAppMissingHash(): void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('Hash parameter is required');

        $this->validator->validateWebApp("query_id=123&user=john");
    }

    public function testValidateWebAppInvalidHash(): void
    {
        $this->expectException(ValidationException::class);

        $initData = "query_id=123&hash=fake_hash";
        $this->validator->validateWebApp($initData);
    }

    // --- Third-Party Validation Tests ---

    public function testValidateWebAppDataForThirdPartySuccess(): void
    {
        // 1. Generate Fake Key Pair for testing
        $keyPair = sodium_crypto_sign_keypair();
        $secretKey = sodium_crypto_sign_secretkey($keyPair);
        $publicKey = sodium_crypto_sign_publickey($keyPair);
        $publicKeyHex = bin2hex($publicKey);

        // 2. Prepare Data
        $botId = 123456;
        $params = [
            'query_id' => 'AA...',
            'user' => '{"id":111}',
            'auth_date' => (string) time(),
        ];

        // 3. Build Check String (BotID:WebAppData\n...)
        ksort($params);
        $checkStringParts = ["$botId:WebAppData"];
        foreach ($params as $k => $v) {
            $checkStringParts[] = "$k=$v";
        }
        $checkString = implode("\n", $checkStringParts);

        // 4. Sign data with our secret key
        $signatureRaw = sodium_crypto_sign_detached($checkString, $secretKey);

        // Custom base64UrlEncode for test data preparation
        $signatureEncoded = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signatureRaw));

        // 5. Build final initData string
        $params['signature'] = $signatureEncoded;
        $initData = http_build_query($params);

        // 6. Assert Validation (Pass OUR public key)
        $this->assertTrue(
            Validator::validateWebAppDataForThirdParty($initData, $botId, $publicKeyHex)
        );

        $this->assertTrue(
            Validator::isValidWebAppDataForThirdParty($initData, $botId, $publicKeyHex)
        );
    }

    public function testValidateThirdPartyMissingSignature(): void
    {
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension is not available');
        }

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionMessage('Signature parameter is required');

        // initData without signature
        Validator::validateWebAppDataForThirdParty("query_id=123", 12345);
    }

    public function testValidateThirdPartyInvalidSignature(): void
    {
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension is not available');
        }

        $this->expectException(ValidationException::class);

        // Generate a random keypair so verification definitely fails against "fake" signature
        $keyPair = sodium_crypto_sign_keypair();
        $publicKeyHex = bin2hex(sodium_crypto_sign_publickey($keyPair));

        $initData = "query_id=123&signature=Zm9vYmFy"; // 'foobar' in base64

        Validator::validateWebAppDataForThirdParty($initData, 12345, $publicKeyHex);
    }

    public function testIsValidThirdPartyReturnsFalseOnFailure(): void
    {
        if (!extension_loaded('sodium')) {
            $this->markTestSkipped('Sodium extension is not available');
        }

        // Random public key
        $keyPair = sodium_crypto_sign_keypair();
        $publicKeyHex = bin2hex(sodium_crypto_sign_publickey($keyPair));

        $this->assertFalse(
            Validator::isValidWebAppDataForThirdParty("query_id=123&signature=bad", 12345, $publicKeyHex)
        );
    }
}
