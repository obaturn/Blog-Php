<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Notifications\SocialActivityNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $groups = Group::query()
            ->with('owner:id,name')
            ->withCount('members')
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($groupQuery) use ($search) {
                    $groupQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(min((int) $request->input('per_page', 15), 50));

        $groups->getCollection()->transform(function (Group $group) use ($request) {
            $group->setAttribute(
                'is_member',
                $request->user()?->groups()->whereKey($group->id)->exists() ?? false
            );

            return $group;
        });

        return response()->json([
            'success' => true,
            'data' => [
                'groups' => $groups->items(),
                'pagination' => [
                    'current_page' => $groups->currentPage(),
                    'per_page' => $groups->perPage(),
                    'total' => $groups->total(),
                    'last_page' => $groups->lastPage(),
                ],
            ],
        ]);
    }

    public function show(Request $request, Group $group): JsonResponse
    {
        $group->load('owner:id,name')->loadCount('members');
        $group->setAttribute(
            'is_member',
            $request->user()?->groups()->whereKey($group->id)->exists() ?? false
        );

        return response()->json(['success' => true, 'data' => ['group' => $group]]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:100', 'unique:groups,name'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_private' => ['sometimes', 'boolean'],
        ]);

        $group = Group::create([
            ...$data,
            'owner_id' => $request->user()->id,
            'slug' => Str::slug($data['name']),
        ]);

        $group->members()->attach($request->user()->id, ['role' => 'owner']);

        return response()->json([
            'success' => true,
            'message' => 'Group created successfully',
            'data' => ['group' => $group->load('owner:id,name')],
        ], 201);
    }

    public function join(Request $request, Group $group): JsonResponse
    {
        if ($group->is_private) {
            return response()->json([
                'success' => false,
                'message' => 'This private group requires an invitation.',
            ], 403);
        }

        $group->members()->syncWithoutDetaching([$request->user()->id => ['role' => 'member']]);
        if ($group->owner_id !== $request->user()->id) {
            $group->owner->notify(new SocialActivityNotification(
                'group_member_joined',
                "{$request->user()->name} joined your group.",
                ['group_id' => $group->id, 'user_id' => $request->user()->id]
            ));
        }

        return response()->json(['success' => true, 'message' => 'You joined the group.']);
    }

    public function leave(Request $request, Group $group): JsonResponse
    {
        if ($group->owner_id === $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'The group owner cannot leave the group.',
            ], 422);
        }

        $group->members()->detach($request->user()->id);

        return response()->json(['success' => true, 'message' => 'You left the group.']);
    }
}