<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Settings"
        title="Privacy"
        description="Who can see your profile, what shows on it, and who can contact you." />

    <form wire:submit="save" class="space-y-6">
        <x-visitor.section title="Profile visibility" description="Controls who can view your public profile page and comment history.">
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ([
                    'public' => ['label' => 'Public', 'icon' => 'globe', 'description' => 'Anyone, including search engines, can view your profile.'],
                    'followers' => ['label' => 'Followers only', 'icon' => 'users', 'description' => 'Only readers who follow you can view it.'],
                    'private' => ['label' => 'Private', 'icon' => 'lock', 'description' => 'Only you. Comments stay attributed to your name but the profile page is hidden.'],
                ] as $value => $opt)
                    <label class="cursor-pointer rounded-xl border p-4 transition
                                  {{ $profileVisibility === $value
                                     ? 'border-emerald-400 bg-emerald-50/50 dark:border-emerald-500/50 dark:bg-emerald-500/10'
                                     : 'border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800' }}">
                        <input type="radio" wire:model="profileVisibility" value="{{ $value }}" class="hidden">
                        <div class="flex items-center gap-2">
                            <i data-lucide="{{ $opt['icon'] }}" class="h-4 w-4 {{ $profileVisibility === $value ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}"></i>
                            <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $opt['label'] }}</span>
                            @if ($profileVisibility === $value)
                                <i data-lucide="check-circle-2" class="ml-auto h-4 w-4 text-emerald-700 dark:text-emerald-400"></i>
                            @endif
                        </div>
                        <p class="mt-2 text-[11px] leading-relaxed text-slate-500 dark:text-slate-400">{{ $opt['description'] }}</p>
                    </label>
                @endforeach
            </div>
        </x-visitor.section>

        <x-visitor.section title="What appears on your profile">
            <div class="-my-2">
                @foreach ([
                    'showReadingHistory' => ['label' => 'Show my reading history', 'description' => 'Other readers can see what you have been reading lately.'],
                    'showFollowers' => ['label' => 'Show my followers list', 'description' => 'Visible only to followers when profile is followers-only.'],
                    'showFollowing' => ['label' => 'Show who I follow', 'description' => 'Authors and readers you follow appear on your profile.'],
                    'allowDms' => ['label' => 'Allow direct messages', 'description' => 'Other readers can send you DMs (feature lands later).'],
                ] as $key => $opt)
                    <div class="flex items-start justify-between gap-4 border-t border-slate-100 py-4 first:border-t-0 dark:border-slate-800">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $opt['label'] }}</p>
                            <p class="mt-0.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $opt['description'] }}</p>
                        </div>
                        <button type="button" wire:click="$toggle('{{ $key }}')"
                                class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full transition
                                       {{ $$key ? 'bg-emerald-600' : 'bg-slate-200 dark:bg-slate-700' }}">
                            <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition
                                          {{ $$key ? 'translate-x-5' : 'translate-x-0.5' }}"></span>
                        </button>
                    </div>
                @endforeach
            </div>
        </x-visitor.section>

        <div class="sticky bottom-4 flex flex-wrap items-center justify-end gap-2 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-md backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
            <button type="submit"
                    wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-60 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                <i data-lucide="save" class="h-4 w-4" wire:loading.remove wire:target="save"></i>
                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="save"></i>
                <span wire:loading.remove wire:target="save">Save preferences</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
