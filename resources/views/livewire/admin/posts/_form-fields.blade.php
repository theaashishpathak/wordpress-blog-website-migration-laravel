{{--
    Shared post form fields — used by Create.php and Edit.php Livewire
    components. Both components expose the same property names:
      title, slug, excerpt, content, type, categoryId, defaultLanguageId,
      tagIds, isFeatured, isBreaking, isTrending, isEditorsPick,
      allowComments, visibility
--}}

<div class="space-y-6">
    {{-- Title --}}
    <div>
        <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <i data-lucide="type" class="h-4 w-4 text-indigo-500"></i>
            Title <span class="text-rose-500">*</span>
        </label>
        <input type="text" wire:model.live.debounce.500ms="title" placeholder="Post title…"
            class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-lg font-medium outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950 dark:focus:ring-indigo-500/20">
        @error('title')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Slug + Type row --}}
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <i data-lucide="link" class="h-3.5 w-3.5 text-slate-400"></i>
                URL Slug
            </label>
            <input type="text" wire:model.live.debounce.500ms="slug" placeholder="auto-generated-from-title"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm font-mono outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950">
            @error('slug')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <i data-lucide="layers" class="h-3.5 w-3.5 text-slate-400"></i>
                Post Type
            </label>
            <select wire:model.live="type"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950">
                @foreach (\App\Enums\PostType::cases() as $caseType)
                    <option value="{{ $caseType->value }}">{{ $caseType->label() }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Excerpt --}}
    <div>
        <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <i data-lucide="align-left" class="h-3.5 w-3.5 text-slate-400"></i>
            Excerpt
        </label>
        <textarea wire:model="excerpt" rows="2" placeholder="Short summary shown in listings…"
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950"></textarea>
        @error('excerpt')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Content — TipTap rich-text editor.

         The TipTap instance lives entirely on the client (Alpine wrapper
         in `resources/js/tiptap-editor.js`). It mirrors changes into a
         hidden textarea bound to $wire->content via wire:model.defer, so
         the existing save flow needs zero changes.

         AI assistant integration: the drawer dispatches
         `editor:set-content` with `{ content: '<p>...</p>' }` to populate
         the editor with a generated draft. --}}
    <div>
        <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <i data-lucide="file-text" class="h-3.5 w-3.5 text-indigo-500"></i>
            Content
        </label>

        <x-admin.rich-editor model="content" :value="$content"
            placeholder="Start writing your article — use H2 for sections, H3 for sub-sections…" />

        @error('content')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Category + Language row --}}
    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <i data-lucide="folder" class="h-3.5 w-3.5 text-slate-400"></i>
                Category
            </label>
            <select wire:model="categoryId"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950">
                <option value="">— uncategorised —</option>
                @foreach ($this->categories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->translate('name') ?? '#' . $cat->id }}</option>
                @endforeach
            </select>
            @error('categoryId')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
                <i data-lucide="globe" class="h-3.5 w-3.5 text-slate-400"></i>
                Default Language
            </label>
            <select wire:model="defaultLanguageId"
                class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950">
                @foreach ($this->languages as $lang)
                    <option value="{{ $lang->id }}">{{ $lang->flag_emoji ?? '' }} {{ $lang->name }}
                        ({{ $lang->code }})
                    </option>
                @endforeach
            </select>
            @error('defaultLanguageId')
                <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    {{-- Tags — Select2 multi-select bootstrapped via Alpine x-init. --}}
    <div>
        <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <i data-lucide="tags" class="h-3.5 w-3.5 text-slate-400"></i>
            Tags
        </label>
        <div wire:ignore x-data x-init="$nextTick(() => {
            const $select = $($el).find('select').first();
            if ($select.length === 0 || $select.hasClass('select2-hidden-accessible')) return;

            $select.select2({
                width: '100%',
                placeholder: 'Pick or search tags…',
                allowClear: true,
                dropdownParent: $($el),
            });

            $select.on('change', function() {
                const value = $(this).val() || [];
                $wire.set('tagIds', value.map(v => parseInt(v, 10)));
            });
        });">
            <select multiple style="width: 100%;">
                @foreach ($this->tags as $tag)
                    <option value="{{ $tag->id }}" {{ in_array($tag->id, $tagIds, true) ? 'selected' : '' }}>
                        {{ $tag->translate('name') ?? $tag->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @error('tagIds')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>

    {{-- Display Flags — colored toggle cards --}}
    <div>
        <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <i data-lucide="sparkle" class="h-3.5 w-3.5 text-slate-400"></i>
            Display Flags
        </h3>
        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @include('livewire.admin.posts._flag-toggle', [
                'name' => 'isFeatured',
                'label' => 'Featured',
                'icon' => 'star',
                'colorClasses' =>
                    'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-300',
                'activeClasses' => 'border-amber-500 bg-amber-100 dark:bg-amber-500/20',
                'state' => $isFeatured,
            ])
            @include('livewire.admin.posts._flag-toggle', [
                'name' => 'isBreaking',
                'label' => 'Breaking',
                'icon' => 'flame',
                'colorClasses' =>
                    'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-500/30 dark:bg-rose-500/10 dark:text-rose-300',
                'activeClasses' => 'border-rose-500 bg-rose-100 dark:bg-rose-500/20',
                'state' => $isBreaking,
            ])
            @include('livewire.admin.posts._flag-toggle', [
                'name' => 'isTrending',
                'label' => 'Trending',
                'icon' => 'trending-up',
                'colorClasses' =>
                    'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-500/30 dark:bg-violet-500/10 dark:text-violet-300',
                'activeClasses' => 'border-violet-500 bg-violet-100 dark:bg-violet-500/20',
                'state' => $isTrending,
            ])
            @include('livewire.admin.posts._flag-toggle', [
                'name' => 'isEditorsPick',
                'label' => "Editor's Pick",
                'icon' => 'award',
                'colorClasses' =>
                    'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-500/30 dark:bg-indigo-500/10 dark:text-indigo-300',
                'activeClasses' => 'border-indigo-500 bg-indigo-100 dark:bg-indigo-500/20',
                'state' => $isEditorsPick,
            ])
            @include('livewire.admin.posts._flag-toggle', [
                'name' => 'allowComments',
                'label' => 'Comments',
                'icon' => 'message-square',
                'colorClasses' =>
                    'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300',
                'activeClasses' => 'border-slate-500 bg-slate-100 dark:bg-slate-700',
                'state' => $allowComments,
            ])
        </div>
    </div>

    {{-- Visibility --}}
    <div>
        <label class="mb-1.5 flex items-center gap-2 text-sm font-semibold text-slate-700 dark:text-slate-200">
            <i data-lucide="eye" class="h-3.5 w-3.5 text-slate-400"></i>
            Visibility
        </label>
        <select wire:model="visibility"
            class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-100 dark:border-slate-700 dark:bg-slate-950">
            <option value="public">🌍 Public — visible to everyone</option>
            <option value="private">🔒 Private — staff only</option>
            <option value="password_protected">🔑 Password Protected</option>
            <option value="premium">💎 Premium / Paywall</option>
        </select>
        @error('visibility')
            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
        @enderror
    </div>
</div>
