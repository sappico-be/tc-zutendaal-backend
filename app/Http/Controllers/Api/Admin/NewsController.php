<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsArticle;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    /**
     * Display a listing of the news articles
     */
    public function index(Request $request)
    {
        $query = NewsArticle::with('author');

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $request->get('per_page', 10);
        $articles = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $articles->items(),
            'meta' => [
                'current_page' => $articles->currentPage(),
                'last_page' => $articles->lastPage(),
                'per_page' => $articles->perPage(),
                'total' => $articles->total(),
            ]
        ]);
    }

    /**
     * Store a newly created news article
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'status' => 'required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'featured_image' => 'nullable|image|max:2048',
            'tags' => 'nullable|array',
        ]);

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            $path = $request->file('featured_image')->store('news', 'public');
            $validated['featured_image'] = $path;
        }

        // Generate slug
        $validated['slug'] = Str::slug($validated['title']);
        
        // Check for duplicate slug
        $originalSlug = $validated['slug'];
        $count = 1;
        while (NewsArticle::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $originalSlug . '-' . $count;
            $count++;
        }

        // Set author
        $validated['author_id'] = auth()->id();

        // Set published_at if publishing
        if ($validated['status'] === 'published' && !isset($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $article = NewsArticle::create($validated);
        $article->load('author');

        return response()->json([
            'success' => true,
            'message' => 'Nieuwsartikel succesvol aangemaakt',
            'data' => $article
        ], 201);
    }

    /**
     * Display the specified news article
     */
    public function show($id)
    {
        $article = NewsArticle::with('author')->findOrFail($id);
        
        return response()->json([
            'success' => true,
            'data' => $article
        ]);
    }

    /**
     * Update the specified news article
     */
    public function update(Request $request, $id)
    {
        $article = NewsArticle::findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'sometimes|required|string',
            'status' => 'sometimes|required|in:draft,published,archived',
            'published_at' => 'nullable|date',
            'featured_image' => 'nullable|image|max:2048',
            'tags' => 'nullable|array',
        ]);

        // Handle image upload
        if ($request->hasFile('featured_image')) {
            // Delete old image if exists
            if ($article->featured_image) {
                Storage::disk('public')->delete($article->featured_image);
            }
            $path = $request->file('featured_image')->store('news', 'public');
            $validated['featured_image'] = $path;
        }

        // Update slug if title changed
        if (isset($validated['title']) && $validated['title'] !== $article->title) {
            $validated['slug'] = Str::slug($validated['title']);
            
            // Check for duplicate slug
            $originalSlug = $validated['slug'];
            $count = 1;
            while (NewsArticle::where('slug', $validated['slug'])->where('id', '!=', $id)->exists()) {
                $validated['slug'] = $originalSlug . '-' . $count;
                $count++;
            }
        }

        // Set published_at if publishing
        if (isset($validated['status']) && $validated['status'] === 'published' && !$article->published_at) {
            $validated['published_at'] = now();
        }

        $article->update($validated);
        $article->load('author');

        return response()->json([
            'success' => true,
            'message' => 'Nieuwsartikel succesvol bijgewerkt',
            'data' => $article
        ]);
    }

    /**
     * Remove the specified news article
     */
    public function destroy($id)
    {
        $article = NewsArticle::findOrFail($id);
        
        // Delete image if exists
        if ($article->featured_image) {
            Storage::disk('public')->delete($article->featured_image);
        }
        
        $article->delete();

        return response()->json([
            'success' => true,
            'message' => 'Nieuwsartikel succesvol verwijderd'
        ]);
    }

    /**
     * Bulk delete articles
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:news_articles,id'
        ]);

        $articles = NewsArticle::whereIn('id', $validated['ids'])->get();
        
        foreach ($articles as $article) {
            if ($article->featured_image) {
                Storage::disk('public')->delete($article->featured_image);
            }
            $article->delete();
        }

        return response()->json([
            'success' => true,
            'message' => count($validated['ids']) . ' artikelen succesvol verwijderd'
        ]);
    }

    /**
     * Update article status
     */
    public function updateStatus(Request $request, $id)
    {
        $article = NewsArticle::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:draft,published,archived'
        ]);

        if ($validated['status'] === 'published' && !$article->published_at) {
            $validated['published_at'] = now();
        }

        $article->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Status succesvol bijgewerkt',
            'data' => $article
        ]);
    }
}
