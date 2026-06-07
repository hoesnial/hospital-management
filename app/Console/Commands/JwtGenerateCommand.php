<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Generate JWT Secret
 *
 * Generates a secure random JWT secret and stores it in .env
 */
class JwtGenerateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'jwt:generate';

    /**
     * The console command description.
     */
    protected $description = 'Generate a new JWT secret key and update .env file';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $secret = 'base64:' . base64_encode(Str::random(64));

        $this->updateEnvironmentFile('JWT_SECRET', $secret);

        $this->components->info('JWT_SECRET generated successfully.');
        $this->components->info('');
        $this->components->info('Key saved to .env file.');

        return 0;
    }

    /**
     * Update the .env file with the new secret
     */
    protected function updateEnvironmentFile(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (!file_exists($envPath)) {
            file_put_contents($envPath, "{$key}={$value}\n");
            return;
        }

        $contents = file_get_contents($envPath);

        if (str_contains($contents, "{$key}=")) {
            $contents = preg_replace(
                "/^{$key}=.*/m",
                "{$key}={$value}",
                $contents
            );
        } else {
            $contents .= "\n{$key}={$value}\n";
        }

        file_put_contents($envPath, $contents);
    }
}
