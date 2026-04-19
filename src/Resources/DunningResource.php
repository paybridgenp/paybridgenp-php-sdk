<?php

namespace PayBridgeNP\Resources;

use PayBridgeNP\HttpClient;

class DunningResource
{
    public function __construct(private readonly HttpClient $http) {}

    public function createPolicy(array $params): array
    {
        return $this->http->post('/v1/billing/dunning/policies', $params);
    }

    public function listPolicies(): array
    {
        return $this->http->get('/v1/billing/dunning/policies');
    }

    public function getPolicy(string $id): array
    {
        return $this->http->get("/v1/billing/dunning/policies/{$id}");
    }

    public function updatePolicy(string $id, array $params): array
    {
        return $this->http->patch("/v1/billing/dunning/policies/{$id}", $params);
    }

    public function setSubscriptionPolicy(string $subscriptionId, ?string $policyId): array
    {
        return $this->http->post(
            "/v1/billing/dunning/subscriptions/{$subscriptionId}/policy",
            ['policyId' => $policyId],
        );
    }

    public function getInvoiceStatus(string $invoiceId): array
    {
        return $this->http->get("/v1/billing/dunning/invoices/{$invoiceId}/dunning");
    }

    public function stopInvoice(string $invoiceId): array
    {
        return $this->http->post("/v1/billing/dunning/invoices/{$invoiceId}/dunning/stop", []);
    }

    public function retryInvoiceNow(string $invoiceId): array
    {
        return $this->http->post("/v1/billing/dunning/invoices/{$invoiceId}/dunning/retry-now", []);
    }
}
