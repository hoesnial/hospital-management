<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\EmailCheckRequest;
use App\Models\User;

class EmailCheckController extends Controller
{
    public function check(EmailCheckRequest $request)
    {
        $validated = $request->validated();

        $exists = User::where('email', $validated['email'])->exists();

        return response()->json([
            'available' => !$exists,
        ]);
    }
}
