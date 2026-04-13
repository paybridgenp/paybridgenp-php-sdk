<?php

declare(strict_types=1);

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class CheckoutResource
{
    /** @var HttpClient */
    private $http;

    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Create a new checkout session.
     *
     * @param array{
     *   amount: int,
     *   return_url: string,
     *   cancel_url?: string,
     *   provider?: string,
     *   currency?: string,
     *   metadata?: array<string,mixed>
     * } $params
     *
     * @return array{
     *   id: string,
     *   checkout_url: string,
     *   expires_at: string,
     *   payment_method?: array<string,mixed>
     * }
     */
    public function create(array $params): array
    {
        // Map PHP-idiomatic snake_case keys to the camelCase keys the API expects
        $body = [];
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'return_url': $body['returnUrl']  = $value; break;
                case 'cancel_url': $body['cancelUrl']  = $value; break;
                default:           $body[$key]         = $value; break;
            }
        }
        return $this->http->post('/v1/checkout', $body);
    }
}
