<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Architecture Tests
|--------------------------------------------------------------------------
|
| Enforces NewsPilot AI layer boundaries. These tests run as part of the
| regular `php artisan test` suite. Any PR that violates a rule will fail
| CI — the only ways to "fix" a failure are to (a) restructure the code
| to honour the boundary, or (b) get team agreement to relax the rule by
| explicitly editing this file.
|
| Reference: CLAUDE.md → "Required Architecture Tests".
|
*/

arch('actions cannot import controllers or livewire')
    ->expect('App\Actions')
    ->not->toUse([
        'App\Http\Controllers',
        'App\Livewire',
    ]);

arch('services cannot import actions')
    ->expect('App\Services')
    ->not->toUse('App\Actions');

arch('models cannot import actions or services')
    ->expect('App\Models')
    ->not->toUse([
        'App\Actions',
        'App\Services',
    ]);

arch('livewire components must extend the Livewire base Component class')
    ->expect('App\Livewire')
    ->classes()
    ->toExtend('Livewire\Component');

arch('visitor portal livewire components live under Visitor namespace')
    ->expect('App\Livewire\Visitor')
    ->classes()
    ->toExtend('Livewire\Component');

arch('visitor actions live under Actions\Visitor namespace')
    ->expect('App\Actions\Visitor')
    ->classes()
    ->toHaveSuffix('Action');

arch('visitor notifications extend Laravel base Notification')
    ->expect('App\Notifications\Reader')
    ->classes()
    ->toExtend('Illuminate\Notifications\Notification');

arch('non-fortify actions must follow the Action suffix naming convention')
    ->expect('App\Actions')
    ->classes()
    ->toHaveSuffix('Action')
    ->ignoring('App\Actions\Fortify');

arch('no direct AI provider class imports outside the AI namespace')
    ->expect('App')
    ->not->toUse([
        'App\Services\AI\NullProvider',
        'App\Services\AI\OpenAIProvider',
        'App\Services\AI\GeminiProvider',
        'App\Services\AI\ClaudeProvider',
        'App\Services\AI\OpenRouterProvider',
    ])
    ->ignoring([
        'App\Services\AI',
        'App\Providers\AIServiceProvider',
    ]);

arch('AI service namespace uses strict types in every file')
    ->expect('App\Services\AI')
    ->toUseStrictTypes();

arch('AI DTOs are immutable readonly classes')
    ->expect('App\Services\AI\DataTransferObjects')
    ->classes()
    ->toBeReadonly();

arch('AI exceptions extend the base AIProviderException')
    ->expect('App\Services\AI\Exceptions')
    ->classes()
    ->toExtend('App\Services\AI\Exceptions\AIProviderException')
    ->ignoring('App\Services\AI\Exceptions\AIProviderException');

arch('AI provider contracts in Contracts namespace are interfaces')
    ->expect('App\Services\AI\Contracts')
    ->toBeInterfaces();
