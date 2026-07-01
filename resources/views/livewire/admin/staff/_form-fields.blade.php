@php($isCreate = $isCreate ?? false)

<div class="space-y-6">
    {{-- Basic info --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Basic Information</h2>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Full Name <span class="text-rose-500">*</span></label>
                <input type="text" wire:model="name" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                @error('name')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Email <span class="text-rose-500">*</span></label>
                <input type="email" wire:model="email" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                @error('email')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Phone</label>
                <input type="text" wire:model="phone" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                @error('phone')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Mobile</label>
                <input type="text" wire:model="mobile" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                @error('mobile')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- Employment --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Employment Details</h2>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Employee ID</label>
                <input type="text" wire:model="employeeId" placeholder="e.g., EMP-001" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                @error('employeeId')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Job Title</label>
                <input type="text" wire:model="jobTitle" placeholder="e.g., Sales Manager" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                @error('jobTitle')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Department</label>
                <select wire:model="departmentId" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="">— Select department —</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
                @error('departmentId')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Reports To (Manager)</label>
                <select wire:model="managerId" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="">— No manager —</option>
                    @foreach ($managers as $mgr)
                        <option value="{{ $mgr->id }}">{{ $mgr->name }} ({{ $mgr->email }})</option>
                    @endforeach
                </select>
                @error('managerId')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Hire Date</label>
                <input type="date" wire:model="hireDate" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                @error('hireDate')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Employment Type</label>
                <select wire:model="employmentType" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="">— Select type —</option>
                    @foreach ($employmentTypes as $type)
                        <option value="{{ $type }}">{{ \Illuminate\Support\Str::headline($type) }}</option>
                    @endforeach
                </select>
                @error('employmentType')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>
    </div>

    {{-- Account & Roles --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Account & Access</h2>

        <div class="mt-4 grid gap-4 md:grid-cols-2">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Status <span class="text-rose-500">*</span></label>
                <select wire:model="status" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    @foreach ($statuses as $statusOption)
                        <option value="{{ $statusOption }}">{{ ucfirst($statusOption) }}</option>
                    @endforeach
                </select>
                @error('status')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <div>
                <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Portal Type <span class="text-rose-500">*</span></label>
                <select wire:model="portalType" class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                    <option value="author">Author (writer / editor)</option>
                    <option value="admin">Admin (full back-office)</option>
                </select>
                @error('portalType')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div class="mt-4">
            <label class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Roles</label>
            <div class="flex flex-wrap gap-2">
                @foreach ($roles as $role)
                    <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">
                        <input type="checkbox" wire:model="selectedRoles" value="{{ $role->name }}" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>{{ $role->name }}</span>
                    </label>
                @endforeach
            </div>
            @error('selectedRoles')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
        </div>
    </div>

    {{-- Password (create only) --}}
    @if ($isCreate)
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-base font-semibold text-slate-900 dark:text-slate-100">Initial Password</h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">Share this temporary password with the staff member during their first login.</p>

            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-slate-700 dark:text-slate-300">Temporary Password <span class="text-rose-500">*</span></label>
                    <div class="flex gap-2">
                        <input type="text" wire:model="temporaryPassword" class="flex-1 rounded-lg border border-slate-200 bg-white px-3 py-2 font-mono text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-200 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100">
                        <button type="button" wire:click="regeneratePassword" class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Regenerate</button>
                    </div>
                    @error('temporaryPassword')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex items-center pt-7">
                    <label class="inline-flex cursor-pointer items-center gap-2">
                        <input type="checkbox" wire:model="sendInvite" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-700 dark:text-slate-300">Require email verification on first login</span>
                    </label>
                </div>
            </div>
        </div>
    @endif
</div>
