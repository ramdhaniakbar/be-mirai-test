<?php

namespace App\Http\Controllers;

use App\Models\Diary;
use App\Models\UserActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class DiaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();

        $diaries = Diary::with(['user:id,name,photo'])
                ->where('user_id', $user->id)
                ->paginate(10);

            $diaries->getCollection()->transform(function ($diary) {
                $diary->image = asset('storage/images/diary/' . $diary->image);

                $diary->user->photo = $diary->user->photo && !str_starts_with($diary->user->photo, 'http')
                    ? asset('storage/images/user/' . $diary->user->photo) 
                    : asset('storage/images/user/account-default.png');

                return $diary;
            });

        return response()->json([
            'status' => 'success',
            'message' => 'Diaries list',
            'data' => $diaries,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|image|mimes:png,jpg',
            'date' => 'required|date',
        ]);

        $user = Auth::user();

        // create new diary
        $diary = new Diary();
        $diary->user_id = $user->id;
        $diary->title = $request->title;
        $diary->description = $request->description;
        $diary->date = $request->date;

        // check if has image
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $image_name = Str::random(10) . '.' . $image->getClientOriginalExtension();
            $image->storeAs('images/diary', $image_name, 'public');
            $diary->image = $image_name;
        } else {
            $diary->image = NULL;
        }

        // save diary
        $diary->save();

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has been added a diary with the title ' . $diary->title,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Diary created successfully',
            'data' => $diary,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // get diary by id where user_id is auth id
        $user = Auth::user();
        $diary = Diary::where('id', $id)->where('user_id', $user->id)->first();

        if (!$diary) {
            return response()->json([
                'status' => 'error',
                'message' => 'Diary not found or you do not have permission to view this diary',
            ], 404);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Diary detail',
            'data' => $diary,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'image|mimes:png,jpg',
            'date' => 'required|date',
        ]);

        // get diay by id where user_id is auth id
        $user = Auth::user();
        $diary = Diary::where('id', $id)->where('user_id', $user->id)->first();

        if (!$diary) {
            return response()->json([
                'status' => 'error',
                'message' => 'Diary not found or you do not have permission to update this diary',
            ], 404);
        }

        $diary->title = $request->title;
        $diary->description = $request->description;
        $diary->date = $request->date;

        if ($request->hasFile('image')) {
            // Delete old image
            if ($diary->image && Storage::disk('public')->exists('images/diary/' . $diary->image)) {
                Storage::disk('public')->delete('images/diary/' . $diary->image);
            }

            // Upload new image
            $image = $request->file('image');
            $image_name = Str::random(10) . '.' . $image->getClientOriginalExtension();
            $image->storeAs('images/diary', $image_name, 'public');
            $diary->image = $image_name;
        }

        // save diary
        $diary->save();

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has been updated a diary with the title ' . $diary->title,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Diary updated successfully',
            'data' => $diary,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // get diary by id where user_id is auth id
        $user = Auth::user();
        $diary = Diary::where('id', $id)->where('user_id', $user->id)->first();

        if (!$diary) {
            return response()->json([
                'status' => 'error',
                'message' => 'Diary not found or you do not have permission to delete this diary',
            ], 404);
        }

        // delete diary
        $diary->delete();

        // user activity log
        UserActivity::create([
            'user_id' => $user->id,
            'activities' => $user->name . ' has been deleted a diary with the title ' . $diary->title,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Diary deleted successfully',
        ]);
    }
}
