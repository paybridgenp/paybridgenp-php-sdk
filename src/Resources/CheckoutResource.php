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
     * `flow` controls the customer experience:
     *  - "hosted" (default) — render the PayBridge picker; `provider`, if set,
     *    is just pre-selected and the customer can still switch.
     *  - "redirect" — skip the picker and 302 the customer straight to the
     *    chosen provider. `provider` is required.
     *
     * `cancel_url` is optional. When omitted, cancellations fall back to
     * `return_url` with `?status=cancelled` appended, and the hosted picker
     * hides its Cancel link.
     *
     * @param array{
     *   amount: int,
     *   return_url: string,
     *   cancel_url?: string,
     *   provider?: string,
     *   flow?: string,
     *   currency?: string,
     *   metadata?: array<string,mixed>,
     *   customer?: array{
     *     name?: string,
     *     email?: string,
     *     phone?: string,
     *     address?: array{
     *       line1: string,
     *       line2?: string,
     *       city: string,
     *       state?: string,
     *       postalCode?: string,
     *       country?: string
     *     }
     *   },
     *   collect_address?: bool
     * } $params
     *
     * @return array{
     *   id: string,
     *   checkout_url: string,
     *   flow: string,
     *   provider: ?string,
     *   expires_at: string
     * }
     */
    public function create(array $params): array
    {
        // Map PHP-idiomatic snake_case keys to the camelCase keys the API expects
        $body = [];
        foreach ($params as $key => $value) {
            switch ($key) {
                case 'return_url':       $body['returnUrl']       = $value; break;
                case 'cancel_url':       $body['cancelUrl']       = $value; break;
                case 'collect_address':  $body['collectAddress']  = $value; break;
                default:                 $body[$key]              = $value; break;
            }
        }
        return $this->http->post('/v1/checkout', $body);
    }
}
