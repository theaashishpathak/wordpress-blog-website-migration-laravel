<?php

declare(strict_types=1);

namespace App\Livewire\Admin\Posts;

use App\Actions\Post\CreatePostRevisionAction;
use App\Actions\Post\UpdatePostAction;
use App\Models\Post;
use App\Models\PostRevision;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

/**
 * Per-post revision history viewer.
 *
 * Each row in `post_revisions` is an immutable snapshot of the post +
 * its translations + tag IDs at a point in time. The component lets an
 * editor:
 *   - Browse all revisions ordered newest first
 *   - Inspect any single snapshot in a side panel
 *   - Restore a previous revision (writes a fresh revision first so the
 *     restore is itself reversible)
 *   - Diff two revisions (simple line-level diff over the default
 *     locale's title + content)
 */
#[Layout('layouts.app')]
#[Title('Post Revisions')]
class Revisions extends Component
{
    public Post $post;

    public ?int $selectedRevisionId = null;

    public ?int $compareRevisionId = null;

    public function mount(Post $post): void
    {
        abort_unless(
            auth()->user()?->can('editorial.revisions') ?? false,
            403,
            'You do not have access to post revisions.',
        );

        $this->post = $post->load('translations');

        // Pick the most recent revision by default for the side panel.
        $this->selectedRevisionId = PostRevision::query()
            ->forPost($post->id)
            ->latestRevision()
            ->value('id');
    }

    /**
     * @return Collection<int, PostRevision>
     */
    #[Computed]
    public function revisions(): Collection
    {
        return PostRevision::query()
            ->forPost($this->post->id)
            ->with('author:id,name')
            ->latestRevision()
            ->get();
    }

    #[Computed]
    public function selectedRevision(): ?PostRevision
    {
        return $this->selectedRevisionId !== null
            ? PostRevision::query()->with('author:id,name')->find($this->selectedRevisionId)
            : null;
    }

    #[Computed]
    public function compareRevision(): ?PostRevision
    {
        return $this->compareRevisionId !== null
            ? PostRevision::query()->find($this->compareRevisionId)
            : null;
    }

    /**
     * Tiny line-level diff between the default-locale title + content of
     * two revisions. Each line is tagged `+`, `-` or ` `.
     *
     * @return list<array{type:string, text:string}>
     */
    #[Computed]
    public function diff(): array
    {
        if ($this->selectedRevision === null || $this->compareRevision === null) {
            return [];
        }

        $a = $this->extractText($this->compareRevision);
        $b = $this->extractText($this->selectedRevision);

        return $this->lineDiff($a, $b);
    }

    private function extractText(PostRevision $rev): string
    {
        $snap = $rev->snapshot ?? [];
        $defaultLocaleId = (int) ($snap['default_language_id'] ?? 0);
        $translations = (array) ($snap['translations'] ?? []);

        $row = collect($translations)
            ->firstWhere('language_id', $defaultLocaleId)
            ?? ($translations[0] ?? []);

        $title = (string) ($row['title'] ?? '');
        $content = strip_tags((string) ($row['content'] ?? ''));

        return trim($title."\n\n".$content);
    }

    /**
     * @return list<array{type:string, text:string}>
     */
    private function lineDiff(string $a, string $b): array
    {
        $aLines = preg_split('/\R/', $a) ?: [];
        $bLines = preg_split('/\R/', $b) ?: [];

        // Naive LCS-free diff: walk both arrays and tag exact matches as
        // context, mismatches as removed-then-added. Adequate for short
        // article text without pulling in a diff library.
        $out = [];
        $max = max(count($aLines), count($bLines));

        for ($i = 0; $i < $max; $i++) {
            $aLine = $aLines[$i] ?? null;
            $bLine = $bLines[$i] ?? null;

            if ($aLine === $bLine) {
                if ($aLine !== null) {
                    $out[] = ['type' => 'context', 'text' => $aLine];
                }
                continue;
            }

            if ($aLine !== null && $aLine !== '') {
                $out[] = ['type' => 'removed', 'text' => $aLine];
            }
            if ($bLine !== null && $bLine !== '') {
                $out[] = ['type' => 'added', 'text' => $bLine];
            }
        }

        return $out;
    }

    public function select(int $revisionId): void
    {
        $this->selectedRevisionId = $revisionId;
    }

    public function compareWith(int $revisionId): void
    {
        $this->compareRevisionId = $revisionId === $this->selectedRevisionId ? null : $revisionId;
    }

    /**
     * Restore the selected revision onto the live post. Writes a fresh
     * revision first so the restore itself shows up in the timeline.
     */
    public function restore(
        UpdatePostAction $updatePost,
        CreatePostRevisionAction $snapshot,
    ): void {
        $this->authorize('editorial.revisions');

        if ($this->selectedRevision === null) {
            $this->dispatchDangerToast('Pick a revision first.');

            return;
        }

        try {
            // Snapshot the current state so the restore is reversible.
            $snapshot->handle(
                $this->post->fresh(),
                authorId: (int) (auth()->id() ?? 0),
                summary: 'Pre-restore checkpoint',
            );

            $snap = $this->selectedRevision->snapshot ?? [];

            $translations = collect((array) ($snap['translations'] ?? []))
                ->map(fn (array $t): array => [
                    'language_id' => $t['language_id'] ?? null,
                    'title' => $t['title'] ?? null,
                    'slug' => $t['slug'] ?? null,
                    'excerpt' => $t['excerpt'] ?? null,
                    'content' => $t['content'] ?? null,
                    'meta_title' => $t['meta_title'] ?? null,
                    'meta_description' => $t['meta_description'] ?? null,
                ])
                ->values()
                ->all();

            $payload = [
                'type' => $snap['type'] ?? $this->post->type->value,
                'category_id' => $snap['category_id'] ?? $this->post->category_id,
                'visibility' => $snap['visibility'] ?? $this->post->visibility,
                'is_featured' => (bool) ($snap['is_featured'] ?? false),
                'is_breaking' => (bool) ($snap['is_breaking'] ?? false),
                'is_trending' => (bool) ($snap['is_trending'] ?? false),
                'is_editors_pick' => (bool) ($snap['is_editors_pick'] ?? false),
                'allow_comments' => (bool) ($snap['allow_comments'] ?? true),
                'translations' => $translations,
                'tag_ids' => (array) ($snap['tag_ids'] ?? []),
                'updated_by' => (int) auth()->id(),
            ];

            $updatePost->handle($this->post, $payload);

            $this->dispatchSuccessToast('Revision restored.');
            $this->post = $this->post->fresh(['translations']);
        } catch (Throwable $e) {
            report($e);
            $this->dispatchDangerToast('Restore failed: '.$e->getMessage());
        }
    }

    public function render(): View
    {
        return view('livewire.admin.posts.revisions');
    }

    protected function dispatchSuccessToast(string $message): void
    {
        $this->dispatch('toast.success', message: $message);
    }

    protected function dispatchDangerToast(string $message): void
    {
        $this->dispatch('toast.danger', message: $message);
    }
}
