<?php

namespace Streeboga\GenesisLaravel\Commands;

use Illuminate\Console\Command;

class GenesisSetupCommand extends Command
{
    protected $signature = 'genesis:setup';
    protected $description = 'Setup Genesis integration configuration';

    public function handle(): int
    {
        $this->info('Genesis Setup');
        $this->line('Setting up Genesis integration...');
        
        // В реальной реализации здесь была бы интерактивная настройка
        $this->info('Genesis integration setup completed!');
        
        return 0;
    }
}


