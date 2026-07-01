@php($title = 'Verify Email')
@extends('layouts.guest')

@section('content')
    <div class="w-full max-w-md rounded-3xl bg-white p-8 shadow-xl dark:bg-slate-900">
        <h1 class="text-2xl font-bold">Verify your email</h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Before continuing, verify your email address using the link we just sent.</p>

        @if (session('status') === 'verification-link-sent')
            <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-500/30 dark:bg-emerald-500/10 dark:text-emerald-300">
                A new verification link has been sent to your email address.
            </div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-6">
            @csrf
            <button type="submit" class="w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white hover:bg-indigo-700">Resend Verification Email</button>
        </form>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="w-full rounded-xl border border-slate-200 px-4 py-3 text-sm font-semibold hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800">Sign Out</button>
        </form>
    </div>
@endsection

