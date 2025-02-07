<?php

namespace App\Http\Controllers;

use App\Models\UserActivity;
use Illuminate\Support\Facades\Storage;

class UserActivityController extends Controller
{
    public function user_activity()
    {
        $user_activities = UserActivity::with(['user:id,name,photo'])
                            ->latest()
                            ->take(10)
                            ->get();
        $total_user_activity = UserActivity::count();

        $user_activities->transform(function ($activity) {
            $activity->user->image_url = $activity->user->photo
                ? url('storage/images/user/' . $activity->user->photo) 
                : url('storage/images/user/account-default.png');
            return $activity;
        });

        return response()->json([
            'status' => 'success',
            'message' => 'User activity list',
            'data' => [
                'data' => $user_activities,
                'total_user_activity' => $total_user_activity
            ]
        ], 200);
    }
}
