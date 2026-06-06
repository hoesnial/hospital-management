<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Mail\StaffWelcomeMail;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class StaffController extends Controller
{
    // List
    public function index(Request $request)
    {
        $rows = Staff::with(['user:id,name,email,photo'])
            ->orderByDesc('id')
            ->get()
            ->map(fn($s) => [
                'id'          => $s->id,
                'user_id'     => $s->user_id,
                'name'        => $s->user?->name,
                'email'       => $s->user?->email,
                'photo'       => $s->user?->photo ? asset('storage/' . $s->user->photo) : null,
                'designation' => $s->designation,
                'department'  => $s->department,
                'phone'       => $s->phone,
                'about'       => $s->about,
            ]);

        return response()->json($rows, 200);
    }

    public function store(StoreStaffRequest $request)
    {
        $data = $request->validated();

        $plain = $data['password'];

        $row = DB::transaction(function () use ($data) {
            $photoPath = null;
            if (isset($data['photo'])) {
                $photoPath = $data['photo']->store('staff', 'public');
            }

            $user = User::create([
                'name'              => $data['name'],
                'email'             => $data['email'],
                'password'          => Hash::make($data['password']),
                'role'              => 'staff',
                'photo'             => $photoPath,
                'email_verified_at' => now(),
            ]);

            $staff = Staff::create([
                'user_id'     => $user->id,
                'designation' => $data['designation'] ?? null,
                'department'  => $data['department'] ?? null,
                'phone'       => $data['phone'] ?? null,
                'about'       => $data['about'] ?? null,
            ]);

            return [$user, $staff];
        });

        [$user, $staff] = $row;


        Mail::to($user->email)->send(new StaffWelcomeMail(
            $user->name,
            $user->email,
            $plain
        ));

        return response()->json([
            'message' => 'Staff member created successfully',
            'staff'  => [
                'id'          => $staff->id,
                'user_id'     => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'photo'       => $user->photo ? asset('storage/' . $user->photo) : null,
                'designation' => $staff->designation,
                'department'  => $staff->department,
                'phone'       => $staff->phone,
                'about'       => $staff->about,
            ]
        ], 201);
    }


    public function update(UpdateStaffRequest $request, Staff $staff)
    {
        $user = $staff->user;

        $data = $request->validated();

        DB::transaction(function () use ($data, $user, $staff) {
            $user->name  = $data['name'];
            $user->email = $data['email'];
            if (!empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }
            if (isset($data['photo'])) {
                $photoPath = $data['photo']->store('staff', 'public');
                $user->photo = $photoPath;
            }
            $user->save();

            $staff->update([
                'designation' => $data['designation'] ?? null,
                'department'  => $data['department'] ?? null,
                'phone'       => $data['phone'] ?? null,
                'about'       => $data['about'] ?? null,
            ]);
        });

        $staff->load('user:id,name,email,photo');

        return response()->json([
            'message' => 'Staff updated',
            'staff'  => [
                'id'          => $staff->id,
                'user_id'     => $staff->user_id,
                'name'        => $staff->user?->name,
                'email'       => $staff->user?->email,
                'photo'       => $staff->user?->photo ? asset('storage/' . $staff->user->photo) : null,
                'designation' => $staff->designation,
                'department'  => $staff->department,
                'phone'       => $staff->phone,
                'about'       => $staff->about,
            ],
        ], 200);
    }


    public function destroy(Staff $staff)
    {
        DB::transaction(function () use ($staff) {
            $user = $staff->user;
            $staff->delete();
            if ($user) $user->delete();
        });

        return response()->noContent();
    }
}
