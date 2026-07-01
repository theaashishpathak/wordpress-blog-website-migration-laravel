@php($title = 'Forgot Password')
@extends('layouts.guest')

@section('content')
    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl dark:bg-slate-900">
        <h1 class="text-2xl font-bold">Forgot your password?</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Enter your email and we will send you a reset link.</p>

        @if (session('status'))
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-4">
            @csrf
            <div>
                <label class="mb-2 block text-sm font-medium">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm outline-none focus:border-indigo-500 dark:border-slate-700 dark:bg-slate-950">
                @error('email')
                    <p class="mt-2 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Email Password Reset Link</button>
            <a href="{{ route('login') }}" class="block text-center text-sm font-semibold text-indigo-600 hover:text-indigo-700">Back to sign in</a>
        </form>
    </div>
@endsection

