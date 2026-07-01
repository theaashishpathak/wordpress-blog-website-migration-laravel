@php
    $user = auth()->user();
    $socialIcons = [
        'twitter' => 'twitter',
        'facebook' => 'facebook',
        'linkedin' => 'linkedin',
        'instagram' => 'instagram',
        'youtube' => 'youtube',
        'website' => 'globe',
    ];
@endphp

<div class="space-y-8">
    <x-visitor.page-header
        eyebrow="Settings"
        title="My Profile"
        description="Your name, email, avatar, and the social links readers see on your public profile." />

    <form wire:submit="save" class="space-y-6">
        <x-visitor.section title="Avatar" description="JPG, PNG or WebP up to 2 MB. Square images render best.">
            <div class="flex flex-wrap items-center gap-5">
                @if ($avatar)
                    <img src="{{ $avatar->temporaryUrl() }}" alt="Preview" class="h-20 w-20 rounded-full object-cover ring-2 ring-emerald-500">
                @elseif ($user->avatar)
                    <img src="{{ $user->avatarUrl() }}" alt="Current avatar" class="h-20 w-20 rounded-full object-cover">
                @else
                    <span class="grid h-20 w-20 place-items-center rounded-full bg-slate-900 text-2xl font-black uppercase text-white dark:bg-slate-100 dark:text-slate-900">
                        {{ mb_substr($user->name, 0, 1) }}
                    </span>
                @endif

                <div class="flex flex-wrap items-center gap-2">
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                        <i data-lucide="upload" class="h-3.5 w-3.5"></i>
                        Choose image
                        <input type="file" wire:model="avatar" accept="image/*" class="hidden">
                    </label>
                    @if ($user->avatar)
                        <button type="button" wire:click="removeAvatar"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-bold text-slate-600 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-rose-500/40 dark:hover:bg-rose-500/10 dark:hover:text-rose-300">
                            <i data-lucide="trash-2" class="h-3.5 w-3.5"></i>
                            Remove
                        </button>
                    @endif
                </div>
            </div>
            @error('avatar') <p class="mt-3 text-xs text-rose-600">{{ $message }}</p> @enderror
        </x-visitor.section>

        <x-visitor.section title="Basics">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Display name</label>
                    <input type="text" wire:model="name"
                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950">
                    @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Email</label>
                    <input type="email" wire:model="email"
                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950">
                    @if ($email !== $user->email)
                        <p class="mt-1.5 flex items-start gap-1 text-[11px] text-amber-700 dark:text-amber-300">
                            <i data-lucide="alert-triangle" class="mt-0.5 h-3 w-3 shrink-0"></i>
                            <span>Changing your email signs you out of every device once verified.</span>
                        </p>
                    @endif
                    @error('email') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Phone <span class="font-normal text-slate-400">(optional)</span></label>
                    <input type="tel" wire:model="phone"
                           class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950">
                    @error('phone') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                </div>
                @if ($email !== $user->email)
                    <div>
                        <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Current password</label>
                        <input type="password" wire:model="currentPassword"
                               class="w-full rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm outline-none transition focus:border-amber-500 focus:ring-1 focus:ring-amber-500 dark:border-amber-500/40 dark:bg-amber-500/10">
                        @error('currentPassword') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
                    </div>
                @endif
            </div>

            <div class="mt-4">
                <label class="mb-1.5 block text-xs font-bold text-slate-700 dark:text-slate-200">Bio</label>
                <textarea wire:model="bio" rows="3" placeholder="Tell other readers a little about yourself…"
                          class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950"></textarea>
                <p class="mt-1 text-[11px] text-slate-500">Shown on your public profile and next to your comments. Up to 500 characters.</p>
                @error('bio') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
            </div>
        </x-visitor.section>

        <x-visitor.section title="Social links" description="Optional. Shown beneath your bio on your public profile.">
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($socialIcons as $platform => $icon)
                    <div>
                        <label class="mb-1.5 flex items-center gap-1.5 text-xs font-bold text-slate-700 dark:text-slate-200">
                            <i data-lucide="{{ $icon }}" class="h-3.5 w-3.5 text-slate-400"></i>
                            {{ ucfirst($platform) }}
                        </label>
                        <input type="text" wire:model="social.{{ $platform }}"
                               placeholder="{{ $platform === 'website' ? 'https://yoursite.com' : '@your-handle or full URL' }}"
                               class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm outline-none transition focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 dark:border-slate-700 dark:bg-slate-950">
                    </div>
                @endforeach
            </div>
        </x-visitor.section>

        {{-- Save bar — sticky, calmer. --}}
        <div class="sticky bottom-4 flex flex-wrap items-center justify-end gap-2 rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-md backdrop-blur dark:border-slate-800 dark:bg-slate-900/95">
            <button type="submit"
                    wire:loading.attr="disabled" wire:target="save"
                    class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-slate-800 disabled:opacity-60 dark:bg-slate-100 dark:text-slate-900 dark:hover:bg-white">
                <i data-lucide="save" class="h-4 w-4" wire:loading.remove wire:target="save"></i>
                <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="save"></i>
                <span wire:loading.remove wire:target="save">Save changes</span>
                <span wire:loading wire:target="save">Saving…</span>
            </button>
        </div>
    </form>
</div>
