<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

trait ApiResponser
{
    protected int $defaultPerPage = 10;

    /**
     * Send success response wrapped in a standard JSON structure.
     */
    protected function success(mixed $data, ?string $message = null, int $code = 200): JsonResponse
    {
        return response()->json([
            'data'    => $data,
            'success' => true,
            'message' => $message,
        ], $code);
    }

    /**
     * Send error response with a standard structure.
     */
    protected function error(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'data'    => null,
            'success' => false,
            'message' => $message,
        ], $code);
    }

    /**
     * Handle invalid query responses.
     */
    protected function invalidQuery(?string $message = null, int $code = 422): JsonResponse
    {
        if (config('app.env') !== 'local') {
            $message = 'Invalid Query';
        }

        return $this->error($message, $code);
    }

    /**
     * Paginate results.
     */
    private function paginate(mixed $collection): LengthAwarePaginator
    {
        Validator::validate(request()->all(), [
            'per_page' => 'integer|min:2|max:50',
        ]);

        $page    = LengthAwarePaginator::resolveCurrentPage();
        $perPage = request()->get('per_page', $this->defaultPerPage);

        $results = $collection->slice(($page - 1) * $perPage, $perPage)->values();

        $paginated = new LengthAwarePaginator(
            $results,
            $collection->count(),
            $perPage,
            $page,
            ['path' => LengthAwarePaginator::resolveCurrentPath()]
        );

        return $paginated->appends(request()->all());
    }

    /**
     * Cache the response.
     */
    private function cacheResponse(mixed $data)
    {
        $url         = request()->url();
        $queryParams = request()->query();
        ksort($queryParams);
        $fullUrl = "{$url}?".http_build_query($queryParams);

        return Cache::remember($fullUrl, 60, fn () => $data);
    }

    protected function authUser(): User
    {
        return Auth::user();
    }
}
