<?php

namespace App\Console\Commands;

use App\Models\Appointment;
use App\Models\BookingDiagnostic;
use App\Models\Prescription;
use App\Models\TestResult;
use App\Services\EncryptionService;
use Illuminate\Console\Command;

class EncryptExistingDataCommand extends Command
{
    protected $signature = 'encrypt:existing-data
                           {--dry-run : Simulate without saving}
                           {--force : Skip confirmation}';

    protected $description = 'Encrypt existing plaintext sensitive data in the database';

    public function handle(EncryptionService $encryption): int
    {
        if (!$this->option('dry-run') && !$this->option('force')) {
            if (!$this->confirm('This will encrypt ALL existing sensitive data. Continue?')) {
                $this->info('Cancelled.');
                return 0;
            }
        }

        $stats = [
            'appointments.additional_notes' => 0,
            'prescriptions.prescription_text' => 0,
            'booking_diagnostic.address' => 0,
            'booking_diagnostic.additional_notes' => 0,
            'test_results.file_path' => 0,
        ];

        $this->info('Encrypting appointment additional_notes...');
        Appointment::whereNotNull('additional_notes')
            ->where('additional_notes', 'not like', 'eyJ%')
            ->chunk(100, function ($appointments) use ($encryption, &$stats) {
                foreach ($appointments as $appointment) {
                    $encrypted = $encryption->encrypt($appointment->getRawOriginal('additional_notes'));
                    if ($encrypted && !$this->option('dry-run')) {
                        $appointment->additional_notes = $encrypted;
                        $appointment->saveQuietly();
                    }
                    $stats['appointments.additional_notes']++;
                }
            });

        $this->info('Encrypting prescription_text...');
        Prescription::whereNotNull('prescription_text')
            ->where('prescription_text', 'not like', 'eyJ%')
            ->chunk(100, function ($prescriptions) use ($encryption, &$stats) {
                foreach ($prescriptions as $prescription) {
                    $encrypted = $encryption->encrypt($prescription->getRawOriginal('prescription_text'));
                    if ($encrypted && !$this->option('dry-run')) {
                        $prescription->prescription_text = $encrypted;
                        $prescription->saveQuietly();
                    }
                    $stats['prescriptions.prescription_text']++;
                }
            });

        $this->info('Encrypting booking_diagnostic address...');
        BookingDiagnostic::whereNotNull('address')
            ->where('address', 'not like', 'eyJ%')
            ->chunk(100, function ($bookings) use ($encryption, &$stats) {
                foreach ($bookings as $booking) {
                    $encrypted = $encryption->encrypt($booking->getRawOriginal('address'));
                    if ($encrypted && !$this->option('dry-run')) {
                        $booking->address = $encrypted;
                        $booking->saveQuietly();
                    }
                    $stats['booking_diagnostic.address']++;
                }
            });

        $this->info('Encrypting booking_diagnostic additional_notes...');
        BookingDiagnostic::whereNotNull('additional_notes')
            ->where('additional_notes', 'not like', 'eyJ%')
            ->chunk(100, function ($bookings) use ($encryption, &$stats) {
                foreach ($bookings as $booking) {
                    $encrypted = $encryption->encrypt($booking->getRawOriginal('additional_notes'));
                    if ($encrypted && !$this->option('dry-run')) {
                        $booking->additional_notes = $encrypted;
                        $booking->saveQuietly();
                    }
                    $stats['booking_diagnostic.additional_notes']++;
                }
            });

        $this->info('Encrypting test_results file_path...');
        TestResult::whereNotNull('file_path')
            ->where('file_path', 'not like', 'eyJ%')
            ->chunk(100, function ($results) use ($encryption, &$stats) {
                foreach ($results as $result) {
                    $encrypted = $encryption->encrypt($result->getRawOriginal('file_path'));
                    if ($encrypted && !$this->option('dry-run')) {
                        $result->file_path = $encrypted;
                        $result->saveQuietly();
                    }
                    $stats['test_results.file_path']++;
                }
            });

        $this->table(
            ['Column', 'Records Processed'],
            collect($stats)->map(fn($count, $column) => [$column, $count])->toArray()
        );

        $total = array_sum($stats);
        if ($this->option('dry-run')) {
            $this->info("Dry-run complete. {$total} records would be encrypted.");
        } else {
            $this->info("Encryption complete. {$total} records encrypted.");
        }

        return 0;
    }
}
