<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserActivity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function user_profile(Request $request)
    {
        $token = $request->user()->currentAccessToken();
        if (!$token) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active token found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'User profile',
            'data' => [
                'user' => Auth::user(),
            ]
        ]);
    }

    public function update_profile(Request $request)
    {
        // validate request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . Auth::id(),
            // 'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|in:male,female,other'
        ]);

        // get user
        $user = User::find(Auth::id());

        // update user
        $user->name = $request->name;
        $user->email = $request->email;
        
        // update additional columns
        $user->date_of_birth = $request->date_of_birth;
        $user->gender = $request->gender;

        if ($request->old_password || $request->password) {
            $request->validate([
                'old_password' => 'required|string',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Old password is incorrect'
                ], 401);
            } else if (password_verify($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'New password must be different from old password'
                ], 400);
            } else if (password_verify($request->old_password, $user->password)) {
                $user->password = Hash::make($request->password);
            }
        }

        if ($request->hasFile('photo')) {
            // check if old photo exists
            if ($user->photo && Storage::disk('public')->exists('images/user/' . $user->photo)) {
                // delete old photo
                Storage::disk('public')->delete('images/user/' . $user->photo);
            }

            // upload new photo
            $photo = $request->file('photo');
            $photo_name = Str::random(10) . '.' . $photo->getClientOriginalExtension();
            $photo->storeAs('images/user', $photo_name, 'public');
            $user->photo = $photo_name;
        } else if (!$request->photo) {
            if ($user->photo && Storage::disk('public')->exists('images/user/' . $user->photo)) {
                // delete old photo
                Storage::disk('public')->delete('images/user/' . $user->photo);
            }
            $user->photo = NULL;
        }

        $user->save();

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has updated their profile',
        ]);

        $user->image_url = $user->photo
            ? url('storage/images/user/' . $user->photo)
            : url('storage/images/user/account-default.png');

        return response()->json([
            'status' => 'success',
            'message' => 'User profile updated successfully',
            'data' => [
                'user' => $user,
            ]
        ]);
    }
}
