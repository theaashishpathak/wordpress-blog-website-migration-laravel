@php($title = 'New Role')

<div>
    <form wire:submit="save" class="space-y-6">
        <x-admin.page-header
            eyebrow="Access Control"
            icon="shield-plus"
            title="New role"
            description="Define a role by picking the permissions it should grant. Users can have many roles.">
            <x-slot:actions>
                <a
                    href="{{ route('admin.roles.index') }}"
                    wire:navigate
                    class="inline-flex h-10 items-center gap-2 rounded-xl border border-slate-200 px-3.5 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Back to roles
                </a>
                <button
                    type="submit"
                    wire:loading.attr="disabled"
                    wire:target="save"
                    class="inline-flex h-10 items-center gap-2 rounded-xl bg-gradient-to-r from-indigo-600 to-violet-600 px-4 text-sm font-semibold text-white shadow-sm transition hover:from-indigo-700 hover:to-violet-700 disabled:opacity-60">
                    <i data-lucide="check" class="h-4 w-4" wire:loading.remove wire:target="save"></i>
                    <i data-lucide="loader-2" class="h-4 w-4 animate-spin" wire:loading wire:target="save"></i>
                    Create role
                </button>
            </x-slot:actions>
        </x-admin.page-header>

        @include('livewire.admin.role.partials.form-fields')
    </form>
</div>
