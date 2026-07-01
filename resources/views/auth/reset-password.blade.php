@php($title = 'Reset Password')
@extends('layouts.guest')

@section('content')
    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl dark:bg-slate-900">
        <h1 class="text-2xl font-bold">Reset password</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Set a new password for your account.</p>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $request->route('token') }}">

            <div>
                <label class="mb-2 block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email', $request->email) }}" required autocomplete="email" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                @error('email')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Password</label>
                <input type="password" name="password" required autocomplete="new-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                @error('password')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium">Confirm Password</label>
                <input type="password" name="password_confirmation" required autocomplete="new-password" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
            </div>

            <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Reset Password</button>
        </form>
    </div>
@endsection

