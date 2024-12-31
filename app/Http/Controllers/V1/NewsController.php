<?php

namespace App\Http\Controllers\V1;

use App\Helpers\ApiResponseHelper;
use App\Http\Controllers\Controller;
use App\Models\News;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NewsController extends Controller
{
    /**
     * Display a listing of the news articles with search and filter options.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            // Fetch query parameters
            $keyword = $request->input('keyword');
            $category = $request->input('category');
            $source = $request->input('source');
            $dateFrom = $request->input('date_from');
            $dateTo = $request->input('date_to');

            // Base query
            $query = News::query();

            // Search by keyword in title or content
            if ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'LIKE', "%$keyword%")
                        ->orWhere('content', 'LIKE', "%$keyword%");
                });
            }

            // Filter by category
            if ($category) {
                $query->where('category', $category);
            }

            // Filter by source
            if ($source) {
                $query->where('source', $source);
            }

            // Filter by date range
            if ($dateFrom) {
                $query->whereDate('published_at', '>=', $dateFrom);
            }
            if ($dateTo) {
                $query->whereDate('published_at', '<=', $dateTo);
            }

            // Paginate results
            $news = $query->orderBy('published_at', 'desc')->paginate(10);

            return ApiResponseHelper::success($news, 'News articles fetched successfully', 200);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('An error occurred', $e->getMessage(), 400);
        }
    }

    public function getPreferences()
    {
        try {
            $preferences = Auth::user()->preference;
            return ApiResponseHelper::success($preferences, 'Preferences fetched successfully', 200);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('An error occurred', $e->getMessage(), 400);
        }
    }

    public function updatePreferences(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'sources' => 'nullable|array',
            'sources.*' => 'string',
            'categories' => 'nullable|array',
            'categories.*' => 'string',
            'authors' => 'nullable|array',
            'authors.*' => 'string',
        ]);

        $preferences = UserPreference::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        return response()->json([
            'status' => 'success',
            'data' => $preferences,
        ]);
    }

    public function getNewsByPreferences(Request $request)
    {
        try {
            $user = Auth::user();

            // Fetch the user's preferences
            $preferences = UserPreference::where('user_id', $user->id)->first();

            if (!$preferences) {
                return ApiResponseHelper::error('Preferences not found', 'Please set your preferences first', 404);
            }

            // Build the query based on preferences
            $query = News::query();

            if (!empty($preferences->sources)) {
                $query->whereIn('source', $preferences->sources);
            }

            if (!empty($preferences->categories)) {
                $query->whereIn('category', $preferences->categories);
            }

            if (!empty($preferences->authors)) {
                $query->whereIn('author', $preferences->authors);
            }

            // Filter by date if provided
            if ($request->has('date')) {
                $query->whereDate('published_at', $request->date);
            }

            $news = $query->paginate(10);

            return ApiResponseHelper::success($news, 'News articles fetched successfully', 200);
        } catch (\Exception $e) {
            return ApiResponseHelper::error('An error occurred', $e->getMessage(), 400);
        }
    }
}
