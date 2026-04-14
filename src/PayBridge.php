<?php

declare(strict_types=1);

namespace PayBridgeNP;

use PayBridgeNP\Resources\CheckoutResource;
use PayBridgeNP\Resources\CustomersResource;
use PayBridgeNP\Resources\InvoicesResource;
use PayBridgeNP\Resources\PaymentsResource;
use PayBridgeNP\Resources\PlansResource;
use PayBridgeNP\Resources\RefundsResource;
use PayBridgeNP\Resources\SubscriptionsResource;
use PayBridgeNP\Resources\WebhooksResource;

/**
 * PayBridge NP PHP SDK
 *
 * @example
 *   $pb = new PayBridge(['api_key' => 'sk_test_...']);
 *
 *   // Create a checkout session
 *   $session = $pb->checkout->create([
 *       'amount'     => 5000,          // NPR 50.00 in paisa
 *       'return_url' => 'https://myshop.com/thank-you',
 *       'cancel_url' => 'https://myshop.com/cart',
 *       'metadata'   => ['order_id' => 'ORD-001'],
 *   ]);
 *   header('Location: ' . $session['checkout_url']);
 *
 *   // Verify a webhook
 *   $event = PayBridge::webhooks()->constructEvent(
 *       file_get_contents('php://input'),
 *       $_SERVER['HTTP_X_PAYBRIDGE_SIGNATURE'] ?? null,
 *       'whsec_...'
 *   );
 */
class PayBridge
{
    /** @var HttpClient */
    private $httpClient;

    /** @var CheckoutResource|null */
    private $checkoutResource;

    /** @var PaymentsResource|null */
    private $paymentsResource;

    /** @var RefundsResource|null */
    private $refundsResource;

    /** @var WebhooksResource|null */
    private $webhooksResource;

    /** @var PlansResource|null */
    private $plansResource;

    /** @var CustomersResource|null */
    private $customersResource;

    /** @var SubscriptionsResource|null */
    private $subscriptionsResource;

    /** @var InvoicesResource|null */
    private $invoicesResource;

    /**
     * @param array{
     *   api_key: string,
     *   base_url?: string,
     *   timeout?: int,
     *   max_retries?: int
     * } $config
     */
    public function __construct(array $config)
    {
        if (empty($config['api_key'])) {
            throw new \InvalidArgumentException('api_key is required');
        }

        $this->httpClient = new HttpClient($config);
    }

    /**
     * Checkout resource — create sessions.
     */
    public function getCheckout(): CheckoutResource
    {
        if ($this->checkoutResource === null) {
            $this->checkoutResource = new CheckoutResource($this->httpClient);
        }
        return $this->checkoutResource;
    }

    /**
     * Payments resource — list and retrieve payments.
     */
    public function getPayments(): PaymentsResource
    {
        if ($this->paymentsResource === null) {
            $this->paymentsResource = new PaymentsResource($this->httpClient);
        }
        return $this->paymentsResource;
    }

    /**
     * Refunds resource — create, list, and retrieve refunds.
     */
    public function getRefunds(): RefundsResource
    {
        if ($this->refundsResource === null) {
            $this->refundsResource = new RefundsResource($this->httpClient);
        }
        return $this->refundsResource;
    }

    /**
     * Webhooks resource — create/list/delete endpoints, verify signatures.
     */
    public function getWebhooks(): WebhooksResource
    {
        if ($this->webhooksResource === null) {
            $this->webhooksResource = new WebhooksResource($this->httpClient);
        }
        return $this->webhooksResource;
    }

    /**
     * Plans resource — create, list, get, update billing plans.
     */
    public function getPlans(): PlansResource
    {
        if ($this->plansResource === null) {
            $this->plansResource = new PlansResource($this->httpClient);
        }
        return $this->plansResource;
    }

    /**
     * Customers resource — create, list, get, update, delete billing customers.
     */
    public function getCustomers(): CustomersResource
    {
        if ($this->customersResource === null) {
            $this->customersResource = new CustomersResource($this->httpClient);
        }
        return $this->customersResource;
    }

    /**
     * Subscriptions resource — create, list, get, pause, resume, cancel, change plan.
     */
    public function getSubscriptions(): SubscriptionsResource
    {
        if ($this->subscriptionsResource === null) {
            $this->subscriptionsResource = new SubscriptionsResource($this->httpClient);
        }
        return $this->subscriptionsResource;
    }

    /**
     * Invoices resource — list and retrieve invoices.
     */
    public function getInvoices(): InvoicesResource
    {
        if ($this->invoicesResource === null) {
            $this->invoicesResource = new InvoicesResource($this->httpClient);
        }
        return $this->invoicesResource;
    }

    /**
     * Magic property access for a more fluent API:
     *   $pb->checkout->create(...)
     *   $pb->payments->list()
     *   $pb->refunds->create(...)
     *   $pb->plans->create(...)
     *   $pb->customers->create(...)
     *   $pb->subscriptions->create(...)
     *   $pb->invoices->list()
     *
     * @return CheckoutResource|PaymentsResource|RefundsResource|WebhooksResource|PlansResource|CustomersResource|SubscriptionsResource|InvoicesResource
     */
    public function __get(string $name)
    {
        switch ($name) {
            case 'checkout':
                return $this->getCheckout();
            case 'payments':
                return $this->getPayments();
            case 'refunds':
                return $this->getRefunds();
            case 'webhooks':
                return $this->getWebhooks();
            case 'plans':
                return $this->getPlans();
            case 'customers':
                return $this->getCustomers();
            case 'subscriptions':
                return $this->getSubscriptions();
            case 'invoices':
                return $this->getInvoices();
        }

        throw new \InvalidArgumentException("Unknown property: {$name}");
    }

    /**
     * Static shortcut for webhook signature verification — no API key needed.
     *
     * @example
     *   $event = PayBridge::webhooks()->constructEvent(
     *       file_get_contents('php://input'),
     *       $_SERVER['HTTP_X_PAYBRIDGE_SIGNATURE'] ?? null,
     *       'whsec_...'
     *   );
     */
    public static function webhooks(): WebhooksResource
    {
        return new WebhooksResource();
    }
}
