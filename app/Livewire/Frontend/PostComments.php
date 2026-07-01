<?php

declare(strict_types=1);

namespace App\Livewire\Frontend;

use App\Actions\Comment\CreateCommentAction;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Throwable;

/**
 * Reader-side comments section — embedded inside PostShow.
 *
 * Renders the approved comment thread + a form for posting a new
 * top-level comment or replying to an existing one. The form is
 * dual-mode (guest fields vs auth'd user) and lays in a honeypot
 * for spam protection.
 */
class PostComments extends Component
{
    public Post $post;

    public string $body = '';

    public string $guestName = '';

    public string $guestEmail = '';

    public string $guestWebsite = '';

    /** Honeypot — humans never see this. */
    public string $hp = '';

    public ?int $replyTo = null;

    public bool $submitted = false;

    public ?string $message = null;

    public ?string $error = null;

    public function startReply(int $commentId): void
    {
        $this->replyTo = $commentId;
        $this->submitted = false;
    }

    public function cancelReply(): void
    {
        $this->replyTo = null;
    }

    public function submit(CreateCommentAction $create): void
    {
        // Bot caught by honeypot — silently succeed.
        if ($this->hp !== '') {
            $this->submitted = true;
            $this->message = 'Thanks for your comment.';

            return;
        }

        $rules = [
            'body' => ['required', 'string', 'min:2', 'max:5000'],
        ];

        if (auth()->user() === null) {
            $rules['guestName'] = ['required', 'string', 'min:2', 'max:120'];
            $rules['guestEmail'] = ['required', 'email:rfc,filter', 'max:255'];
            $rules['guestWebsite'] = ['nullable', 'url', 'max:255'];
        }

        $this->validate($rules);

        try {
            $comment = $create->handle($this->post, auth()->user(), [
                'parent_id' => $this->replyTo,
                'body' => $this->body,
                'guest_name' => $this->guestName,
                'guest_email' => $this->guestEmail,
                'guest_website' => $this->guestWebsite,
                'ip' => request()->ip(),
                'user_agent' => (string) request()->userAgent(),
            ]);

            $this->submitted = true;
            $this->message = $comment->isApproved()
                ? 'Your comment is live — thanks for joining the conversation.'
                : "Thanks! Your comment is waiting for moderation.";

            $this->reset(['body', 'replyTo']);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (Throwable $exception) {
            report($exception);
            $this->error = 'Could not post your comment. Please try again.';
        }
    }

    /**
     * Top-level approved comments + their approved replies (1 level deep).
     *
     * @return Collection<int, Comment>
     */
    #[Computed]
    public function thread(): Collection
    {
        return Comment::query()
            ->with(['author:id,name,avatar', 'replies' => fn ($q) => $q->approved()->oldest(), 'replies.author:id,name,avatar'])
            ->where('post_id', $this->post->id)
            ->approved()
            ->topLevel()
            ->oldest()
            ->get();
    }

    #[Computed]
    public function totalCount(): int
    {
        return Comment::query()
            ->where('post_id', $this->post->id)
            ->approved()
            ->count();
    }

    public function render(): View
    {
        return view('livewire.frontend.post-comments');
    }
}
