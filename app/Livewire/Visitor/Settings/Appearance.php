<?php

declare(strict_types=1);

namespace App\Livewire\Visitor\Settings;

use App\Models\Language;
use App\Models\UserSetting;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Appearance — theme, language, font size, reading width. The theme value
 * also seeds the localStorage `crm-theme` key on next page load (the early
 * <head> script reads from localStorage, so saving here dispatches to
 * Alpine which writes it). Other values render via .prose-fontsize-* and
 * .prose-width-* utility classes wired in app.css.
 */
#[Layout('layouts.visitor')]
#[Title('Appearance')]
class Appearance extends Component
{
    public string $theme = 'system';

    public string $fontSize = 'medium';

    public string $readingWidth = 'medium';

    public ?int $languageId = null;

    public function mount(): void
    {
        $uid = auth()->id();
        $this->theme = (string) UserSetting::getValue($uid, 'theme', 'system');
        $this->fontSize = (string) UserSetting::getValue($uid, 'font_size', 'medium');
        $this->readingWidth = (string) UserSetting::getValue($uid, 'reading_width', 'medium');

        $rawLang = UserSetting::getValue($uid, 'language_id');
        $this->languageId = is_numeric($rawLang) ? (int) $rawLang : (auth()->user()->locale ? null : null);
    }

    public function save(): void
    {
        $this->validate([
            'theme' => 'required|in:light,dark,system',
            'fontSize' => 'required|in:small,medium,large,xlarge',
            'readingWidth' => 'required|in:narrow,medium,wide',
            'languageId' => 'nullable|integer|exists:languages,id',
        ]);

        $uid = auth()->id();
        UserSetting::setValue($uid, 'theme', $this->theme);
        UserSetting::setValue($uid, 'font_size', $this->fontSize);
        UserSetting::setValue($uid, 'reading_width', $this->readingWidth);

        if ($this->languageId !== null) {
            UserSetting::setValue($uid, 'language_id', $this->languageId);

            $lang = Language::query()->find($this->languageId);
            if ($lang) {
                auth()->user()->forceFill(['locale' => $lang->code])->save();
            }
        }

        // Push to localStorage so the early <head> theme script picks it up
        // without a full app reload. Browser sync handled in blade.
        $this->dispatch('apply-theme', theme: $this->theme);
        $this->dispatch('toast', message: 'Appearance saved.');
    }

    public function render(): View
    {
        return view('livewire.visitor.settings.appearance', [
            'languages' => Language::query()->where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'flag_emoji']),
        ]);
    }
}
