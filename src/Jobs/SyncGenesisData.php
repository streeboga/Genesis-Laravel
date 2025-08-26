<?php

namespace Streeboga\GenesisLaravel\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Streeboga\Genesis\GenesisClient;
use Streeboga\GenesisLaravel\Services\GenesisCacheService;

class SyncGenesisData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $projectId,
        protected string $dataType
    ) {
    }

    public function handle(GenesisClient $genesis, GenesisCacheService $cache): void
    {
        match ($this->dataType) {
            'users' => $this->syncUsers($genesis, $cache),
            'billing' => $this->syncBilling($genesis, $cache),
            'features' => $this->syncFeatures($genesis, $cache),
            default => \Log::warning('Unknown data type for sync', ['type' => $this->dataType])
        };
    }

    public function getProjectId(): string
    {
        return $this->projectId;
    }

    public function getDataType(): string
    {
        return $this->dataType;
    }

    protected function syncUsers(GenesisClient $genesis, GenesisCacheService $cache): void
    {
        try {
            $users = $genesis->users->list($this->projectId);
            $cache->put("users:{$this->projectId}", $users, 1800); // 30 minutes
            \Log::info('Synced users data', ['project' => $this->projectId, 'count' => count($users)]);
        } catch (\Exception $e) {
            \Log::error('Failed to sync users', ['project' => $this->projectId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function syncBilling(GenesisClient $genesis, GenesisCacheService $cache): void
    {
        try {
            $plans = $genesis->billing->listPlans($this->projectId);
            $cache->put("billing:plans:{$this->projectId}", $plans, 3600); // 1 hour
            \Log::info('Synced billing data', ['project' => $this->projectId, 'plans' => count($plans)]);
        } catch (\Exception $e) {
            \Log::error('Failed to sync billing', ['project' => $this->projectId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    protected function syncFeatures(GenesisClient $genesis, GenesisCacheService $cache): void
    {
        try {
            $features = $genesis->features->list($this->projectId);
            $cache->put("features:{$this->projectId}", $features, 7200); // 2 hours
            \Log::info('Synced features data', ['project' => $this->projectId, 'count' => count($features)]);
        } catch (\Exception $e) {
            \Log::error('Failed to sync features', ['project' => $this->projectId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}


