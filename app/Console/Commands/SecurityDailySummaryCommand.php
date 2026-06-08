<?php

namespace App\Console\Commands;

use App\Services\TelegramAlertService;
use Illuminate\Console\Command;

class SecurityDailySummaryCommand extends Command
{
    protected $signature = 'security:daily-summary';

    protected $description = 'Send daily security summary via Telegram';

    public function handle()
    {
        $this->info('Sending daily security summary...');

        try {
            $telegram = new TelegramAlertService();
            $result = $telegram->sendDailySummary();

            if ($result) {
                $this->info('✅ Daily summary sent successfully');

                return 0;
            } else {
                $this->error('❌ Failed to send daily summary');

                return 1;
            }
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());

            return 1;
        }
    }
}
