<?php

namespace Streeboga\GenesisLaravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Streeboga\Genesis\GenesisClient;

class ProcessGenesisWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected array $webhookData
    ) {
    }

    public function handle(GenesisClient $genesis): void
    {
        $event = $this->getEvent();
        $data = $this->webhookData['data'] ?? [];

        // Process different webhook events
        match ($event) {
            'payment.completed' => $this->handlePaymentCompleted($data, $genesis),
            'user.created' => $this->handleUserCreated($data, $genesis),
            'subscription.updated' => $this->handleSubscriptionUpdated($data, $genesis),
            default => \Log::info('Unhandled Genesis webhook event', ['event' => $event, 'data' => $data])
        };
    }

    public function getWebhookData(): array
    {
        return $this->webhookData;
    }

    public function getEvent(): string
    {
        return $this->webhookData['event'] ?? '';
    }

    protected function handlePaymentCompleted(array $data, GenesisClient $genesis): void
    {
        \Log::info('Processing payment completed webhook', $data);
        // Implementation for payment completed
    }

    protected function handleUserCreated(array $data, GenesisClient $genesis): void
    {
        \Log::info('Processing user created webhook', $data);
        // Implementation for user created
    }

    protected function handleSubscriptionUpdated(array $data, GenesisClient $genesis): void
    {
        \Log::info('Processing subscription updated webhook', $data);
        // Implementation for subscription updated
    }
}






