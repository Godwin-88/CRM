<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ApiResource;
use App\Models\DiscussionBoard;
use App\Models\DiscussionThread;
use Illuminate\Http\Request;

class DiscussionThreadController extends Controller
{
    public function store(Request $request, string $type, string $id)
    {
        $modelClass = match ($type) {
            'accounts' => \App\Models\Account::class,
            'deals' => \App\Models\Deal::class,
            default => abort(404),
        };

        $model = $modelClass::findOrFail($id);
        $this->authorize('update', $model);

        $board = $model->discussionBoard()->firstOrCreate(['title' => 'Discussion']);

        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
        ]);

        $thread = $board->threads()->create([
            'user_id' => $request->user()->id,
            'title' => $request->input('title'),
            'body' => $request->input('body'),
        ]);

        return ApiResource::make($thread->load('author'));
    }

    public function show(string $type, string $id, string $thread)
    {
        $modelClass = match ($type) {
            'accounts' => \App\Models\Account::class,
            'deals' => \App\Models\Deal::class,
            default => abort(404),
        };

        $model = $modelClass::findOrFail($id);
        $board = $model->discussionBoard()->firstOrFail();
        $thread = $board->threads()->findOrFail($thread);

        $this->authorize('view', $model);

        return ApiResource::make($thread->load(['author', 'replies.author']));
    }

    public function update(Request $request, string $type, string $id, string $thread)
    {
        $modelClass = match ($type) {
            'accounts' => \App\Models\Account::class,
            'deals' => \App\Models\Deal::class,
            default => abort(404),
        };

        $model = $modelClass::findOrFail($id);
        $board = $model->discussionBoard()->firstOrFail();
        $thread = $board->threads()->findOrFail($thread);

        $this->authorize('update', $thread);

        $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'body' => 'sometimes|required|string',
        ]);

        $thread->update($request->only(['title', 'body']));

        return ApiResource::make($thread->load(['author', 'replies.author']));
    }

    public function destroy(Request $request, string $type, string $id, string $thread)
    {
        $modelClass = match ($type) {
            'accounts' => \App\Models\Account::class,
            'deals' => \App\Models\Deal::class,
            default => abort(404),
        };

        $model = $modelClass::findOrFail($id);
        $board = $model->discussionBoard()->firstOrFail();
        $thread = $board->threads()->findOrFail($thread);

        $this->authorize('delete', $thread);

        $thread->delete();

        return ApiResource::make(['deleted' => true]);
    }
}