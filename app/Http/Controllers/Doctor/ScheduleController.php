<?php
namespace App\Http\Controllers\Doctor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\StoreScheduleRequest;
use App\Http\Requests\Doctor\UpdateScheduleRequest;
use App\Models\Schedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ScheduleController extends Controller
{
    public function index(Request $req)
{
    $user = \Illuminate\Support\Facades\Auth::user();

    
    if (!$user) 
    {
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

   
    $doctor = $user->doctor;
    if (!$doctor)
     {
       
        return response()->json(['message' => 'No doctor profile found for this user. Please create doctor profile first.'], 422);    }

   
    $items = $doctor->schedules()
        ->orderByRaw("FIELD(day_of_week,'sat','sun','mon','tue','wed','thu','fri')")
        ->get();

    return response()->json($items, 200);
}


    public function store(StoreScheduleRequest $req)
    {
        $doctor = Auth::user()->doctor;

        $data = $req->validated();
       
        $duration = Carbon::parse($data['start_time'])->diffInMinutes(Carbon::parse($data['end_time']));
        if ($duration < $data['slot_minutes']) {
            return response()->json(['message'=>'Slot minutes must be <= total duration'], 422);
        }

        $schedule = Schedule::create(array_merge(
            ['doctor_id' => $doctor->id],
            $data
        ));


        return $schedule;
    }

    public function update(UpdateScheduleRequest $req, $id)
{
    $doctor = Auth::user()->doctor;
    if (!$doctor) 
    {
        return response()->json(['message' => 'No doctor profile found'], 422);
    }
   
    $schedule = Schedule::where('doctor_id', $doctor->id)->findOrFail($id);

    $data = $req->validated();

    $duration = Carbon::parse($data['start_time'])
        ->diffInMinutes(Carbon::parse($data['end_time']));
    if ($duration < $data['slot_minutes']) {
        return response()->json(['message' => 'Slot minutes must be <= total duration'], 422);
    }

    $schedule->update($data);
    return $schedule->refresh();
}

    public function destroy($id)
	{
    $doctor = Auth::user()->doctor;
    if (!$doctor) {
        return response()->json(['message' => 'No doctor profile found'], 422);
    }
    
    $schedule = Schedule::where('doctor_id', $doctor->id)->findOrFail($id);

    $schedule->delete();
    return response()->noContent();
	}
}
