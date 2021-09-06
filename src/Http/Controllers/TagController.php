<?php

declare(strict_types=1);

namespace Canvas\Http\Controllers;

use Canvas\Http\Requests\StoreTagRequest;
use Canvas\Models\Tag;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Ramsey\Uuid\Uuid;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): JsonResponse
    {
        return response()->json(
            Tag::select('id', 'name', 'created_at')
               ->latest()
               ->withCount('posts')
               ->paginate()
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(): JsonResponse
    {
        return response()->json(Tag::make([
            'id' => Uuid::uuid4()->toString(),
        ]));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreTagRequest $request
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(StoreTagRequest $request, $id): JsonResponse
    {
        $data = $request->validated();

        $tag = Tag::find($id);

        if (! $tag) {
            if ($tag = Tag::onlyTrashed()->firstWhere('slug', $data['slug'])) {
                $tag->restore();

                return response()->json($tag->refresh(), 201);
            } else {
                $tag = new Tag(['id' => $id]);
            }
        }

        $tag->fill($data);

        $tag->user_id = $tag->user_id ?? request()->user('canvas')->id;

        $tag->save();

        return response()->json($tag->refresh(), 201);
    }

    /**
     * Display the specified resource.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id): JsonResponse
    {
        $tag = Tag::findOrFail($id);

        return response()->json($tag);
    }

    /**
     * Display the specified relationship.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function posts($id): JsonResponse
    {
        $tag = Tag::with('posts')->findOrFail($id);

        return response()->json($tag->posts()->withCount('views')->paginate());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     * @throws Exception
     */
    public function destroy($id): JsonResponse
    {
        $tag = Tag::findOrFail($id);

        $tag->delete();

        return response()->json(null, 204);
    }
}
