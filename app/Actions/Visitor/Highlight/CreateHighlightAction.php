<?php

declare(strict_types=1);

namespace App\Actions\Visitor\Highlight;

use App\Models\Highlight;
use App\Models\Post;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreateHighlightAction
{
    /**
     * @param  array{
     *     selected_text: string,
     *     note?: ?string,
     *     start_offset?: ?int,
     *     end_offset?: ?int,
     *     language_id?: ?int,
     * }  $data
     */
    public function handle(User $user, Post $post, array $data): Highlight
    {
        $text = trim($data['selected_text'] ?? '');

        if ($text === '') {
            throw ValidationException::withMessages([
                'selected_text' => 'Highlighted text cannot be empty.',
            ]);
        }

        if (mb_strlen($text) > 2000) {
            $text = mb_substr($text, 0, 2000);
        }

        return Highlight::query()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'language_id' => $data['language_id'] ?? null,
            'selected_text' => $text,
            'note' => isset($data['note']) ? trim((string) $data['note']) : null,
            'start_offset' => $data['start_offset'] ?? null,
            'end_offset' => $data['end_offset'] ?? null,
            'context_hash' => sha1($text),
        ]);
    }
}
