<div class="space-y-6">
    {{-- Breadcrumb --}}
    <nav class="flex items-center gap-2 text-xs text-slate-500">
        <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-indigo-600">Dashboard</a>
        <i data-lucide="chevron-right" class="h-3 w-3"></i>
        <span class="font-semibold text-slate-700 dark:text-slate-200">Author Profile</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <span class="grid h-10 w-10 place-items-center rounded-2xl bg-gradient-to-br from-violet-500 to-indigo-500 text-white shadow-sm">
                <i data-lucide="user-cog" class="h-5 w-5"></i>
            </span>
            <div>
                <h1 class="text-lg font-bold text-slate-900 dark:text-slate-100">Author Profile</h1>
                <p class="text-xs text-slate-500">
                    Public byline shown on your articles + author page.
                </p>
            </div>
        </div>

        @if ($this->publicUrl)
            <a href="{{ $this->publicUrl }}" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">
                <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                View public page
            </a>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        {{-- Form --}}
        <div class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Display name <span class="text-rose-500">*</span>
                </label>
                <input type="text" wire:model="displayName"
                       class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950">
                @error('displayName') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Public slug (vanity URL)
                </label>
                <div class="flex items-center gap-2">
                    <span class="font-mono text-xs text-slate-400">/author/</span>
                    <input type="text" wire:model="publicSlug" placeholder="your-name"
                           class="flex-1 rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-mono dark:border-slate-700 dark:bg-slate-950">
                </div>
                @error('publicSlug') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-semibold text-slate-700 dark:text-slate-200">
                    Bio
                </label>
                <textarea wire:model="bio" rows="5"
                          placeholder="Tell readers about yourself, your expertise, what you write about…"
                          class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm dark:border-slate-700 dark:bg-slate-950"></textarea>
                <p class="mt-1 text-xs text-slate-500">Markdown is not parsed — keep it short and human.</p>
                @error('bio') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <h3 class="mb-3 text-sm font-semibold text-slate-700 dark:text-slate-200">Social links</h3>
                <div class="grid gap-3 sm:grid-cols-2">
                    @foreach (['twitter' => 'twitter', 'facebook' => 'facebook', 'linkedin' => 'linkedin', 'instagram' => 'instagram', 'youtube' => 'youtube', 'website' => 'globe'] as $platform => $icon)
                        <div>
                            <label class="mb-1 flex items-center gap-1.5 text-xs font-semibold text-slate-600 dark:text-slate-300">
                                <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5"></i>
                                {{ ucfirst($platform) }}
                            </label>
                            <input type="text"
                                   wire:model="social.{{ $platform }}"
                                   placeholder="@yourhandle or https://…"
                                   class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs dark:border-slate-700 dark:bg-slate-950">
                            @error('social.'.$platform) <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                        </div>
                    @endforeach
                </div>
            </div>

            <label class="flex items-center gap-2 rounded-xl bg-slate-50 p-3 text-sm font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <input type="checkbox" wire:model="showInTeam"
                       class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                Show on the public Team page
            </label>
        </div>

        {{-- Sidebar — save + preview --}}
        <aside class="space-y-4">
            <button type="button" wire:click="save"
                    wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-indigo-500 px-4 py-3 text-sm font-bold text-white shadow-sm hover:from-indigo-700 hover:to-indigo-600 disabled:opacity-60">
                <i data-lucide="save" class="h-4 w-4" wire:loading.remove wire:target="save"></i>
                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="save"></i>
                <span wire:loading.remove wire:target="save">Save Profile</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-slate-500">Preview</h3>
                <div class="rounded-xl bg-gradient-to-br from-indigo-50 via-violet-50 to-fuchsia-50 p-4 text-center dark:from-indigo-500/10 dark:via-violet-500/10 dark:to-fuchsia-500/10">
                    <span class="grid h-14 w-14 mx-auto place-items-center rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 text-xl font-black uppercase text-white shadow">
                        {{ mb_substr($displayName ?: '?', 0, 1) }}
                    </span>
                    <p class="mt-2 text-sm font-bold text-slate-800 dark:text-slate-100">{{ $displayName ?: '—' }}</p>
                    @if ($bio)
                        <p class="mt-1 text-xs text-slate-600 dark:text-slate-300">{{ \Illuminate\Support\Str::limit($bio, 120) }}</p>
                    @endif
                    <div class="mt-3 flex items-center justify-center gap-2">
                        @foreach (['twitter' => 'twitter', 'facebook' => 'facebook', 'linkedin' => 'linkedin', 'instagram' => 'instagram', 'youtube' => 'youtube', 'website' => 'globe'] as $platform => $icon)
                            @if (! empty($social[$platform]))
                                <span class="grid h-7 w-7 place-items-center rounded-lg bg-white text-slate-600 shadow-sm dark:bg-slate-800 dark:text-slate-300">
                                    <i data-lucide="{{ $icon }}" class="h-3 w-3"></i>
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>
