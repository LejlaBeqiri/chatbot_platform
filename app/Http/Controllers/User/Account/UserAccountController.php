<?php

namespace App\Http\Controllers\User\Account;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserAccountController extends Controller
{
    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'old_password' => 'required|string|max:255',
            'new_password' => 'required|string|min:8|max:255|confirmed',
        ]);

        $user = $this->authUser();

        if (! $user || ! Hash::check($data['old_password'], $user->password)) {
            return $this->error('Wrong credentials', 401);
        }

        if ($user->isDirty('password')) {
            return $this->error('cannot change the password');
        }

        $user->password = bcrypt($data['new_password']);

        $user->save();

        return $this->success(new UserResource($user));
    }
}
