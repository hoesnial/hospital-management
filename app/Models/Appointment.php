<?php

namespace App\Models;

use App\Models\Concerns\Encryptable;
use Illuminate\Database\Eloquent\Model;
use App\Models\Prescription;

class Appointment extends Model
{
    use Encryptable;

    protected $fillable = [
        'booking_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'age',
        'preferred_date',
        'preferred_time',
        'speciality',
        'doctor_id',
        'additional_notes',
        'status',
    ];

    protected array $encryptable = [
        'additional_notes',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function getDoctorNameAttribute()
    {
        return $this->doctor ? $this->doctor->user->name : null;
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }
}
