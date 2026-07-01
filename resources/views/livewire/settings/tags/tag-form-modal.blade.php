<div class="fixed inset-0 z-50 flex">
    <div class="absolute inset-0 bg-slate-900/60" wire:click="cancel"></div>

    <div class="relative ml-auto flex h-full w-full max-w-2xl flex-col bg-white shadow-2xl dark:bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
            <h2 class="text-lg font-semibold">{{ $tagId ? 'Edit Tag' : 'Create Tag' }}</h2>
            <button wire:click="cancel" type="button" class="rounded-xl p-2 text-lg leading-none hover:bg-slate-100 dark:hover:bg-slate-800">&times;</button>
        </div>

        <form wire:submit="save" class="flex-1 space-y-4 overflow-y-auto p-6">
            <div>

                <div>
                    <label class="mb-2 block text-sm font-medium">Color</label>
                    <input wire:model="color" type="color" class="h-12 w-full rounded-xl border border-slate-200 bg-white px-2 py-2 dark:border-slate-700 dark:bg-slate-950">
                    @error('color')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Name</label>
                <input wire:model.live="name" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                @error('name')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Slug</label>
                <input wire:model.live="slug" type="text" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                @error('slug')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-sm font-medium">Type</label>
                    <select wire:model="type" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        <option value="contact">Contact</option>
                        <option value="lead">Lead</option>
                        <option value="deal">Deal</option>
                        <option value="service">Service</option>
                    </select>
                    @error('type')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium">Status</label>
                    <select wire:model="status" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                        <option value="published">Published</option>
                        <option value="unpublished">Unpublished</option>
                    </select>
                    @error('status')
                        <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center justify-end gap-2 border-t border-slate-200 pt-4 dark:border-slate-800">
                <button wire:click="cancel" type="button" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Cancel</button>
                <button type="submit" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">Save Tag</button>
            </div>
        </form>
    </div>
</div>
