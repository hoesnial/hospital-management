<?php

namespace App\Models;

use App\Models\Concerns\Encryptable;
use Illuminate\Database\Eloquent\Model;

class TestResult extends Model
{
    use Encryptable;

    protected $fillable = [
        'booking_id',
        'file_path',
        'delivered_at',
    ];

    protected array $encryptable = [
        'file_path',
    ];

    public function booking()
    {
        return $this->belongsTo(BookingDiagnostic::class);
    }
}
