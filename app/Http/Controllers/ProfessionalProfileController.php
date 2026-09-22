<?php

namespace App\Http\Controllers;

use App\Models\ProfessionalProfile;
use App\Models\ProfileEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfessionalProfileController extends Controller
{
    public function show(Request $request, User $user): JsonResponse
    {
        $profile = $user->professionalProfile()->with('entries')->first();

        if (!$profile || ($profile->visibility === 'private' && $request->user()?->id !== $user->id)) {
            return response()->json([
                'success' => false,
                'message' => 'Professional profile not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => $profile,
                'user' => ['id' => $user->id, 'name' => $user->name],
            ],
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'headline' => ['nullable', 'string', 'max:160'],
            'summary' => ['nullable', 'string', 'max:3000'],
            'location' => ['nullable', 'string', 'max:160'],
            'website_url' => ['nullable', 'url', 'max:2048'],
            'github_url' => ['nullable', 'url', 'max:2048'],
            'linkedin_url' => ['nullable', 'url', 'max:2048'],
            'skills' => ['nullable', 'array', 'max:30'],
            'skills.*' => ['string', 'min:1', 'max:60'],
            'visibility' => ['sometimes', 'in:public,private'],
            'availability' => ['sometimes', 'in:open_to_work,not_looking,freelance'],
        ]);

        $profile = $request->user()->professionalProfile()->updateOrCreate(
            ['user_id' => $request->user()->id],
            $data
        );

        return response()->json([
            'success' => true,
            'message' => 'Professional profile updated successfully.',
            'data' => ['profile' => $profile->load('entries')],
        ]);
    }

    public function addEntry(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:experience,education,certification,project'],
            'title' => ['required', 'string', 'max:160'],
            'organization' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:3000'],
            'started_at' => ['nullable', 'date'],
            'ended_at' => ['nullable', 'date', 'after_or_equal:started_at'],
            'is_current' => ['sometimes', 'boolean'],
        ]);

        $profile = $request->user()->professionalProfile()->firstOrCreate(['user_id' => $request->user()->id]);
        $entry = $profile->entries()->create($data);

        return response()->json([
            'success' => true,
            'message' => 'Professional profile entry added.',
            'data' => ['entry' => $entry],
        ], 201);
    }

    public function deleteEntry(Request $request, ProfileEntry $entry): JsonResponse
    {
        abort_unless($entry->profile->user_id === $request->user()->id, 403);
        $entry->delete();

        return response()->json(['success' => true, 'message' => 'Profile entry deleted.']);
    }
}