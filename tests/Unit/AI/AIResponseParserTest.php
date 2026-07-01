<?php

declare(strict_types=1);

use App\Services\AI\AIResponseParser;
use App\Services\AI\Exceptions\AIProviderException;
use Tests\TestCase;

uses(TestCase::class);

test('extracts pure JSON object', function (): void {
    $result = AIResponseParser::extractJson('{"foo": "bar", "n": 42}');

    expect($result)->toBe(['foo' => 'bar', 'n' => 42]);
});

test('extracts JSON from inside ```json code fence', function (): void {
    $content = <<<'OUT'
        Here is your data:
        ```json
        {
          "meta_title": "AI Tools 2026",
          "tags": ["ai", "tools"]
        }
        ```
        OUT;

    $result = AIResponseParser::extractJson($content);

    expect($result['meta_title'])->toBe('AI Tools 2026');
    expect($result['tags'])->toBe(['ai', 'tools']);
});

test('extracts JSON from inside plain ``` code fence', function (): void {
    $content = "```\n{\"x\": 1}\n```";

    expect(AIResponseParser::extractJson($content))->toBe(['x' => 1]);
});

test('extracts JSON from surrounding noise', function (): void {
    $content = 'Sure, here you go: {"key": "value"} hope this helps!';

    expect(AIResponseParser::extractJson($content))->toBe(['key' => 'value']);
});

test('extracts JSON arrays as well as objects', function (): void {
    $content = '["a", "b", "c"]';

    expect(AIResponseParser::extractJson($content))->toBe(['a', 'b', 'c']);
});

test('handles nested braces correctly', function (): void {
    $content = '{"outer": {"inner": {"deep": 1}}, "list": [1, 2, 3]}';

    $result = AIResponseParser::extractJson($content);
    expect($result['outer']['inner']['deep'])->toBe(1);
    expect($result['list'])->toBe([1, 2, 3]);
});

test('handles escaped quotes inside string values', function (): void {
    $content = '{"quote": "She said \"hello\""}';

    expect(AIResponseParser::extractJson($content))->toBe(['quote' => 'She said "hello"']);
});

test('throws AIProviderException when no JSON block found', function (): void {
    AIResponseParser::extractJson('I cannot help with that request.');
})->throws(AIProviderException::class, 'did not contain any JSON');

test('throws AIProviderException when JSON is malformed', function (): void {
    AIResponseParser::extractJson('{"broken": "json", missing": "quote"}');
})->throws(AIProviderException::class);

test('error message includes provider name when supplied', function (): void {
    try {
        AIResponseParser::extractJson('not json', providerName: 'gemini');
        $this->fail('Expected exception');
    } catch (AIProviderException $e) {
        expect($e->providerName)->toBe('gemini');
    }
});
