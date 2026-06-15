<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\CommentCreated;
use App\Events\CommentUpdated;
use App\Http\Controllers\Controller;
use App\Http\Resources\ApiCollection;
use App\Http\Resources\ApiResource;
use App\Jobs\SendMentionNotification;
use App\Models\Comment;
use App\Models\User;
use App\Services\CommentSanitizationService;
use App\Services\MentionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CommentController extends Controller
{
    public function __construct(
        protected CommentSanitizationService $sanitizer,
        protected MentionService $mentionService
    ) {}

    public function index(Request $request, string $type, string $id): ApiCollection
    {
        $model = $this->resolveModel($type, $id);
        $this->authorize('view', $model);

        $comments = $model->comments()
            ->with(['user', 'mentions.user'])
            ->orderBy('created_at', 'asc')
            ->paginate(20);

        return ApiCollection::make($comments);
    }

    public function store(Request $request, string $type, string $id): ApiResource
    {
        $model = $this->resolveModel($type, $id);
        $this->authorize('update', $model);

        $request->validate([
            'body' => 'required|string|min:1',
            'order' => 'sometimes|in:asc,desc',
        ]);

        $body = $request->input('body');
        if (trim($body) === '') {
            throw ValidationException::withMessages([
                'body' => 'Comment body cannot be empty.',
            ]);
        }

        $cleanBody = $this->sanitizer->clean($body);
        $mentions = $this->sanitizer->extractMentions($body);

        $comment = $model->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $cleanBody,
        ]);

        $this->syncMentions($comment, $mentions, $model);

        SendMentionNotification::dispatch($comment, $model, $mentions);

        event(new CommentCreated($comment, $model));

        return ApiResource::make($comment->load(['user', 'mentions.user']));
    }

    public function show(string $type, string $id, string $commentId): ApiResource
    {
        $model = $this->resolveModel($type, $id);
        $comment = $model->comments()->findOrFail($commentId);

        $this->authorize('view', $model);

        return ApiResource::make($comment->load(['user', 'mentions.user']));
    }

    public function update(Request $request, string $type, string $id, string $commentId): ApiResource
    {
        $model = $this->resolveModel($type, $id);
        $comment = $model->comments()->findOrFail($commentId);

        $this->authorize('update', $comment);

        if (! $comment->canEdit($request->user())) {
            abort(403, 'Comments cannot be edited after 15 minutes.');
        }

        $request->validate([
            'body' => 'required|string|min:1',
        ]);

        $cleanBody = $this->sanitizer->clean($request->input('body'));
        $mentions = $this->sanitizer->extractMentions($request->input('body'));

        $comment->update([
            'body' => $cleanBody,
            'edited_at' => now(),
        ]);

        $this->syncMentions($comment, $mentions, $model);

        event(new CommentUpdated($comment, $model));

        return ApiResource::make($comment->load(['user', 'mentions.user']));
    }

    public function destroy(Request $request, string $type, string $id, string $commentId): ApiResource
    {
        $model = $this->resolveModel($type, $id);
        $comment = $model->comments()->findOrFail($commentId);

        $this->authorize('delete', $comment);

        $comment->update([
            'deleted_at' => now(),
            'deleted_by_id' => $request->user()->id,
        ]);

        return ApiResource::make([
            'deleted' => true,
            'deleted_at' => $comment->deleted_at,
            'deleted_by' => $request->user()->only(['id', 'name']),
        ]);
    }

    protected function resolveModel(string $type, string $id)
    {
        $modelClass = match ($type) {
            'contacts' => \App\Models\Contact::class,
            'accounts' => \App\Models\Account::class,
            'deals' => \App\Models\Deal::class,
            'tickets' => \App\Models\Ticket::class,
            'contracts' => \App\Models\Contract::class,
            'campaigns' => \App\Models\Campaign::class,
            default => abort(404),
        };

        return $modelClass::findOrFail($id);
    }

    protected function syncMentions(Comment $comment, array $mentionIds, $model): void
    {
        $validUsers = User::whereIn('id', $mentionIds)->get()->keyBy('id');
        $existingMentionIds = $comment->mentions()->pluck('user_id')->toArray();

        foreach ($mentionIds as $userId) {
            if ($validUsers->has($userId)) {
                \App\Models\CommentMention::updateOrCreate([
                    'comment_id' => $comment->id,
                    'user_id' => $userId,
                ], [
                    'read_at' => null,
                ]);
            }
        }

        $comment->mentions()->whereNotIn('user_id', $mentionIds)->delete();
    }
}