<?php

namespace App\Http\Controllers;

use App\Models\Diary;
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
        $total_diary = Diary::count();

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
                'total_diary' => $total_diary
            ]
        ], 200);
    }
}
