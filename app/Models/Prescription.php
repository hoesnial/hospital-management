<?php

namespace App\Models;

use App\Models\Concerns\Encryptable;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use Encryptable;

    protected $fillable = [
        'appointment_id',
        'prescription_text',
    ];

    protected array $encryptable = [
        'prescription_text',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
