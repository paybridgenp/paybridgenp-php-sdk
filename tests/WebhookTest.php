<?php

declare(strict_types=1);

namespace PayBridgeNP\Tests;

use PHPUnit\Framework\TestCase;
use PayBridgeNP\PayBridge;
use PayBridgeNP\Resources\WebhooksResource;
use PayBridgeNP\Exceptions\SignatureVerificationException;

class WebhookTest extends TestCase
{
    private const SECRET = 'whsec_test_secret_key_for_unit_tests_only';

    private function makeSignature(string $payload, int $timestamp): string
    {
        $hmac = hash_hmac('sha256', $timestamp . '.' . $payload, self::SECRET);
        return 't=' . $timestamp . ',v1=' . $hmac;
    }

    public function testStaticWebhooksReturnsResource(): void
    {
        $this->assertInstanceOf(WebhooksResource::class, PayBridge::webhooks());
    }

    public function testValidSignatureReturnsEvent(): void
    {
        $payload   = json_encode(['id' => 'evt_123', 'type' => 'payment.succeeded', 'created' => time(), 'data' => []]);
        $timestamp = time();
        $signature = $this->makeSignature($payload, $timestamp);

        $event = PayBridge::webhooks()->constructEvent($payload, $signature, self::SECRET);

        $this->assertSame('evt_123', $event['id']);
        $this->assertSame('payment.succeeded', $event['type']);
    }

    public function testMissingSignatureThrows(): void
    {
        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('Missing X-PayBridge-Signature header');

        PayBridge::webhooks()->constructEvent('{}', null, self::SECRET);
    }

    public function testEmptySignatureThrows(): void
    {
        $this->expectException(SignatureVerificationException::class);

        PayBridge::webhooks()->constructEvent('{}', '', self::SECRET);
    }

    public function testMalformedSignatureThrows(): void
    {
        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('Malformed');

        PayBridge::webhooks()->constructEvent('{}', 'not-a-valid-signature', self::SECRET);
    }

    public function testWrongSecretThrows(): void
    {
        $payload   = json_encode(['type' => 'payment.succeeded']);
        $timestamp = time();
        $signature = $this->makeSignature($payload, $timestamp);

        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('signature verification failed');

        PayBridge::webhooks()->constructEvent($payload, $signature, 'whsec_wrong_secret');
    }

    public function testTamperedPayloadThrows(): void
    {
        $payload   = json_encode(['type' => 'payment.succeeded', 'amount' => 100]);
        $timestamp = time();
        $signature = $this->makeSignature($payload, $timestamp);

        $tamperedPayload = json_encode(['type' => 'payment.succeeded', 'amount' => 99999]);

        $this->expectException(SignatureVerificationException::class);

        PayBridge::webhooks()->constructEvent($tamperedPayload, $signature, self::SECRET);
    }

    public function testOldTimestampThrows(): void
    {
        $payload   = json_encode(['type' => 'payment.succeeded']);
        $timestamp = time() - 400; // 400 seconds old — exceeds 300s window
        $signature = $this->makeSignature($payload, $timestamp);

        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('Timestamp too old');

        PayBridge::webhooks()->constructEvent($payload, $signature, self::SECRET);
    }

    public function testFutureTimestampWithinWindowSucceeds(): void
    {
        $payload   = json_encode(['id' => 'evt_future', 'type' => 'payment.failed', 'created' => time(), 'data' => []]);
        $timestamp = time() + 60; // 60 seconds in the future — within 300s window
        $signature = $this->makeSignature($payload, $timestamp);

        $event = PayBridge::webhooks()->constructEvent($payload, $signature, self::SECRET);
        $this->assertSame('payment.failed', $event['type']);
    }

    public function testInvalidJsonPayloadThrows(): void
    {
        $payload   = 'not-valid-json';
        $timestamp = time();
        $signature = $this->makeSignature($payload, $timestamp);

        $this->expectException(SignatureVerificationException::class);
        $this->expectExceptionMessage('Invalid webhook payload');

        PayBridge::webhooks()->constructEvent($payload, $signature, self::SECRET);
    }

    public function testConstructorRequiresApiKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('api_key is required');

        new PayBridge([]);
    }

    public function testWebhooksInstanceMethodRequiresHttpClient(): void
    {
        $this->expectException(\RuntimeException::class);

        // Static webhooks() has no HTTP client — calling create() should throw
        PayBridge::webhooks()->create(['url' => 'https://example.com']);
    }
}
