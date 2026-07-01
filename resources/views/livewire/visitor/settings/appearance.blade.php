<div class="space-y-8"
     x-data
     x-on:apply-theme.window="(() => {
         const theme = $event.detail.theme;
         localStorage.setItem('crm-theme', theme);
         const shouldUseDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
         document.documentElement.classList.toggle('dark', shouldUseDark);
     })()">

    <x-visitor.page-header
        eyebrow="Settings"
        title="Appearance"
        description="Theme, language, font size, and reading width — applied site-wide." />

    <form wire:submit="save" class="space-y-6">
        <x-visitor.section title="Theme">
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ([
                    'light' => ['label' => 'Light', 'icon' => 'sun', 'preview' => 'bg-slate-50 border-slate-200'],
                    'dark' => ['label' => 'Dark', 'icon' => 'moon', 'preview' => 'bg-slate-900 border-slate-700'],
                    'system' => ['label' => 'Match system', 'icon' => 'monitor', 'preview' => 'bg-gradient-to-br from-slate-50 to-slate-900 border-slate-300'],
                ] as $value => $opt)
                    <label class="cursor-pointer rounded-xl border p-4 transition
                                  {{ $theme === $value
                                     ? 'border-emerald-400 dark:border-emerald-500/50'
                                     : 'border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800' }}">
                        <input type="radio" wire:model="theme" value="{{ $value }}" class="hidden">
                        <div class="mb-3 aspect-[16/9] w-full rounded-lg border {{ $opt['preview'] }}"></div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="{{ $opt['icon'] }}" class="h-4 w-4 {{ $theme === $value ? 'text-emerald-700 dark:text-emerald-400' : 'text-slate-500 dark:text-slate-400' }}"></i>
                            <span class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $opt['label'] }}</span>
                            @if ($theme === $value)
                                <i data-lucide="check-circle-2" class="ml-auto h-4 w-4 text-emerald-700 dark:text-emerald-400"></i>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        </x-visitor.section>

        <x-visitor.section title="Reading language" description="Sets the locale shown when you visit the public site. Multi-language content falls back to the post's default language when a translation is missing.">
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($languages as $lang)
                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition
                                  {{ $languageId === $lang->id
                                     ? 'border-emerald-400 bg-emerald-50/50 dark:border-emerald-500/50 dark:bg-emerald-500/10'
                                     : 'border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800' }}">
                        <input type="radio" wire:model="languageId" value="{{ $lang->id }}" class="hidden">
                        <span class="text-2xl leading-none">{{ $lang->flag_emoji ?? '🌐' }}</span>
                        <span class="min-w-0">
                            <span class="block text-sm font-bold text-slate-900 dark:text-slate-100">{{ $lang->name }}</span>
                            <span class="block font-mono text-[10px] uppercase tracking-wider text-slate-500">{{ $lang->code }}</span>
                        </span>
                        @if ($languageId === $lang->id)
                            <i data-lucide="check" class="ml-auto h-4 w-4 text-emerald-700 dark:text-emerald-400"></i>
                        @endif
                    </label>
                @endforeach
            </div>
        </x-visitor.section>

        <x-visitor.section title="Article font size">
            <div class="grid gap-3 sm:grid-cols-4">
                @foreach ([
                    'small' => ['label' => 'Small', 'preview' => 'text-xs'],
                    'medium' => ['label' => 'Default', 'preview' => 'text-sm'],
                    'large' => ['label' => 'Large', 'preview' => 'text-base'],
                    'xlarge' => ['label' => 'X-Large', 'preview' => 'text-lg'],
                ] as $value => $opt)
                    <label class="cursor-pointer rounded-lg border p-4 text-center transition
                                  {{ $fontSize === $value
                                     ? 'border-emerald-400 bg-emerald-50/50 dark:border-emerald-500/50 dark:bg-emerald-500/10'
                                     : 'border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800' }}">
                        <input type="radio" wire:model="fontSize" value="{{ $value }}" class="hidden">
                        <p class="{{ $opt['preview'] }} font-black text-slate-900 dark:text-slate-100" style="font-family: 'Playfair Display', serif;">Aa</p>
                        <p class="mt-1.5 text-xs font-bold text-slate-700 dark:text-slate-300">{{ $opt['label'] }}</p>
                    </label>
                @endforeach
            </div>
        </x-visitor.section>

        <x-visitor.section title="Reading width">
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach ([
                    'narrow' => ['label' => 'Narrow', 'description' => 'Compact, easier on the eye', 'preview' => 'h-3 w-20'],
                    'medium' => ['label' => 'Default', 'description' => 'Comfortable, balanced lines', 'preview' => 'h-3 w-32'],
                    'wide' => ['label' => 'Wide', 'description' => 'Edge-to-edge on big screens', 'preview' => 'h-3 w-44'],
                ] as $value => $opt)
                    <label class="cursor-pointer rounded-lg border p-4 transition
                                  {{ $readingWidth === $value
                                     ? 'border-emerald-400 bg-emerald-50/50 dark:border-emerald-500/50 dark:bg-emerald-500/10'
                                     : 'border-slate-200 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800' }}">
                        <input type="radio" wire:model="readingWidth" value="{{ $value }}" class="hidden">
                        <div class="mb-3 flex items-center justify-center">
                            <span class="{{ $opt['preview'] }} rounded-full bg-slate-300 dark:bg-slate-600"></span>
                        </div>
                        <p class="text-sm font-bold text-slate-900 dark:text-slate-100">{{ $opt['label'] }}</p>
                        <p class="text-[11px] text-slate-500">{{ $opt['description'] }}</p>
                    </label>
                @endforeach
            </div>
        </x-visitor.section>

        <div class="sticky bottom-4 flex flex-wrap items-center justify-end gap-2 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-md backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
            <button type="submit"
                    wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-60 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                <i data-lucide="save" class="h-4 w-4" wire:loading.remove wire:target="save"></i>
                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="save"></i>
                <span wire:loading.remove wire:target="save">Save appearance</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
