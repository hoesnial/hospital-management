<?php

namespace App\Console\Commands;

use App\Services\TelegramAlertService;
use Illuminate\Console\Command;

class TelegramTestCommand extends Command
{
    protected $signature = 'telegram:test';

    protected $description = 'Test Telegram alert service connection';

    public function handle()
    {
        $this->info('Testing Telegram connection...');

        try {
            $telegram = new TelegramAlertService();
            $result = $telegram->testConnection();

            if ($result) {
                $this->info('✅ Telegram connection successful!');

                return 0;
            } else {
                $this->error('❌ Telegram connection failed - check configuration');

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());

            return 1;
        }
    }
}
