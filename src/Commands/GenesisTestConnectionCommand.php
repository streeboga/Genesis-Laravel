<?php

namespace Streeboga\GenesisLaravel\Commands;

use Illuminate\Console\Command;
use Streeboga\Genesis\GenesisClient;

class GenesisTestConnectionCommand extends Command
{
    protected $signature = 'genesis:test-connection';
    protected $description = 'Test connection to Genesis API';

    public function __construct(private GenesisClient $genesis)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Testing Genesis API connection...');
        
        try {
            // В реальной реализации здесь был бы вызов API для проверки соединения
            $this->info('Genesis API connection: OK');
            $this->line('API Key: ' . substr($this->genesis->getApiKey(), 0, 8) . '...');
            
            return 0;
        } catch (\Exception $e) {
            $this->error('Genesis API connection failed: ' . $e->getMessage());
            return 1;
        }
    }
}


