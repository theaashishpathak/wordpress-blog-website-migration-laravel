@php($title = 'Manage Tags')

<div class="space-y-6" x-data="{ formOpen: @entangle('showFormModal') }">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-bold">Manage Tags</h1>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Create and organize contact, lead, deal, and service tags.</p>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <button wire:click="exportCsv" type="button" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold hover:bg-white dark:border-slate-800 dark:hover:bg-slate-900">Export</button>
            <button x-on:click="formOpen = true; $wire.create()" type="button" class="rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">+ Add Tag</button>
        </div>
    </div>

    <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-slate-900">
        <div class="grid gap-4 lg:grid-cols-4">
            <div class="lg:col-span-2">
                <label class="mb-2 block text-sm font-medium">Search</label>
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search by code or name..." class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Type</label>
                <select wire:model.live="typeFilter" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                    <option value="">All Types</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}">{{ ucfirst($type) }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Status</label>
                <select wire:model.live="statusFilter" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                    <option value="">All Statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}">{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>

    <div class="rounded-xl bg-white p-5 shadow-sm dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                <thead class="text-left text-slate-500 dark:text-slate-400">
                    <tr>
                        <th class="py-3 pr-4 font-medium">SL</th>
                        <th class="py-3 pr-4 font-medium">Code</th>
                        <th class="py-3 pr-4 font-medium">Name</th>
                        <th class="py-3 pr-4 font-medium">Type</th>
                        <th class="py-3 pr-4 font-medium">Status</th>
                        <th class="py-3 pr-4 font-medium">Created By</th>
                        <th class="py-3 pr-4 font-medium">Updated At</th>
                        <th class="py-3 pr-4 font-medium text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($tags as $tag)
                        <tr>
                            <td class="py-4 pr-4">{{ ($tags->firstItem() ?? 0) + $loop->index }}</td>
                            <td class="py-4 pr-4 font-semibold">{{ $tag->code }}</td>
                            <td class="py-4 pr-4">
                                <div class="font-medium">{{ $tag->name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $tag->slug }}</div>
                            </td>
                            <td class="py-4 pr-4">
                                <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200">{{ ucfirst($tag->type) }}</span>
                            </td>
                            <td class="py-4 pr-4">
                                <span class="rounded-full px-3 py-1 text-xs font-semibold {{ $tag->status === 'published' ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-200' : 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200' }}">{{ ucfirst($tag->status) }}</span>
                            </td>
                            <td class="py-4 pr-4 text-slate-600 dark:text-slate-300">{{ $tag->createdBy?->name ?? '-' }}</td>
                            <td class="py-4 pr-4 text-slate-500 dark:text-slate-400">{{ $tag->updated_at?->format('M d, Y h:i A') ?? '-' }}</td>
                            <td class="py-4 pr-4">
                                <div class="flex justify-end gap-2">
                                    <button wire:click="view({{ $tag->id }})" type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800">View</button>
                                    <button x-on:click="formOpen = true; $wire.edit({{ $tag->id }})" type="button" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800">Edit</button>
                                    <button wire:click="requestDelete({{ $tag->id }})" type="button" class="rounded-lg border border-rose-200 px-3 py-2 text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:border-rose-500/30 dark:hover:bg-rose-500/10">Delete</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-6 text-center text-slate-500 dark:text-slate-400">No tags found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($tags->hasPages())
            <div class="mt-4">
                {{ $tags->links() }}
            </div>
        @endif
    </div>

    @if ($showFormModal)
        <livewire:settings.tags.tag-form-modal :tag-id="$editingTagId" :open="$showFormModal" :key="$formKey" />
    @endif

    @if ($viewingTag !== null)
        <div class="fixed inset-0 z-50 flex">
            <div class="absolute inset-0 bg-slate-900/60" wire:click="closeViewModal"></div>

            <div class="relative ml-auto flex h-full w-full max-w-2xl flex-col bg-white shadow-2xl dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4 dark:border-slate-800">
                    <h2 class="text-lg font-semibold">Tag Details</h2>
                    <button wire:click="closeViewModal" type="button" class="rounded-xl p-2 text-lg leading-none hover:bg-slate-100 dark:hover:bg-slate-800">&times;</button>
                </div>

                <div class="flex-1 space-y-4 overflow-y-auto p-6">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div><p class="text-xs text-slate-500">Code</p><p class="mt-1 font-medium">{{ $viewingTag['code'] }}</p></div>
                        <div><p class="text-xs text-slate-500">Name</p><p class="mt-1 font-medium">{{ $viewingTag['name'] }}</p></div>
                        <div><p class="text-xs text-slate-500">Slug</p><p class="mt-1 font-medium">{{ $viewingTag['slug'] }}</p></div>
                        <div>
                            <p class="text-xs text-slate-500">Color</p>
                            <div class="mt-1 flex items-center gap-2">
                                <span class="h-5 w-5 rounded-full border border-slate-300" style="background-color: {{ $viewingTag['color'] }}"></span>
                                <p class="font-medium">{{ $viewingTag['color'] }}</p>
                            </div>
                        </div>
                        <div><p class="text-xs text-slate-500">Created By</p><p class="mt-1 font-medium">{{ $viewingTag['created_by'] }}</p></div>
                        <div><p class="text-xs text-slate-500">Created At</p><p class="mt-1 font-medium">{{ $viewingTag['created_at'] }}</p></div>
                        <div><p class="text-xs text-slate-500">Updated By</p><p class="mt-1 font-medium">{{ $viewingTag['updated_by'] }}</p></div>
                        <div><p class="text-xs text-slate-500">Updated At</p><p class="mt-1 font-medium">{{ $viewingTag['updated_at'] }}</p></div>
                        <div><p class="text-xs text-slate-500">Status</p><p class="mt-1 font-medium">{{ ucfirst($viewingTag['status']) }}</p></div>
                        <div><p class="text-xs text-slate-500">Type</p><p class="mt-1 font-medium">{{ ucfirst($viewingTag['type']) }}</p></div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        $wire.on('confirm-tag-delete-alert', (event) => {
            const tagId = Number(event.id ?? 0);
            if (tagId <= 0) {
                return;
            }

            const confirmDeletion = () => {
                $wire.confirmDelete(tagId);
            };

            if (!window.Swal) {
                confirmDeletion();
                return;
            }

            window.Swal.fire({
                title: 'Delete tag?',
                text: 'This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4f46e5',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, delete it',
            }).then((result) => {
                if (result.isConfirmed) {
                    confirmDeletion();
                }
            });
        });
    </script>
    @endscript
</div>
