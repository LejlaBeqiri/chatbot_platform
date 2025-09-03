<?php

namespace App\Http\Controllers\Admin\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Traits\ApiResponser;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserController extends Controller
{
    use ApiResponser;

    public function index(): JsonResponse
    {
        $users = User::with('tenant')->paginate($this->defaultPerPage);

        return $this->success(UserResource::collection($users));
    }

    public function show(User $user): JsonResponse
    {
        $user->load('tenant');

        return $this->success(new UserResource($user));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $user->update($validated);

        return $this->success(new UserResource($user));
    }

    public function destroy(User $user): JsonResponse
    {
        DB::beginTransaction();
        try {
            if ($user->tenant) {
                $user->tenant->delete();
            }
            $user->delete();
            DB::commit();

            return $this->success(['message' => 'User deleted successfully']);
        } catch (Exception $e) {
            DB::rollBack();

            return $this->error($e->getMessage());
        }
    }
}
