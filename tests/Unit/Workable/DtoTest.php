<?php

use App\Integrations\Workable\DTO\ApplicationSchema;
use App\Integrations\Workable\DTO\JobPostData;
use App\Integrations\Workable\Exceptions\WorkableException;

function workableFixture(string $name): array
{
    return json_decode(file_get_contents(__DIR__."/../../Fixtures/workable/{$name}"), true);
}

it('parses a job from the fixture', function () {
    $data = workableFixture('jobs_published.json');
    $job = JobPostData::fromApi($data['jobs'][0]);

    expect($job->shortcode)->toBe('SENIO001')
        ->and($job->externalId)->toBe('a1b2c3d4')
        ->and($job->title)->toBe('Senior Backend Engineer')
        ->and($job->state)->toBe('published')
        ->and($job->workplaceType)->toBe('remote')
        ->and($job->location)->not->toBeEmpty()
        ->and($job->salary['salary_currency'])->toBe('USD')
        ->and($job->contentHtml)->toContain('<strong>backend engineer</strong>')
        ->and($job->contentHtml)->toContain('PHP &amp; Laravel')
        ->and($job->externalUpdatedAt)->toBeInstanceOf(\DateTimeImmutable::class);
});

it('parses a job with missing optionals', function () {
    $data = workableFixture('jobs_published.json');
    $job = JobPostData::fromApi($data['jobs'][1]);

    expect($job->salary)->toBeNull()
        ->and($job->keywords)->toBe([])
        ->and($job->department)->toBeNull()
        ->and($job->contentHtml)->toBeNull();
});

it('throws without shortcode or title', function () {
    expect(fn () => JobPostData::fromApi(['title' => 'x']))->toThrow(WorkableException::class);
    expect(fn () => JobPostData::fromApi(['shortcode' => 'x']))->toThrow(WorkableException::class);
});

it('builds a schema from application_form', function () {
    $schema = ApplicationSchema::fromApi(workableFixture('application_form.json'));

    $byKey = collect($schema->formFields)->keyBy('key');
    expect($byKey['email']['required'])->toBeTrue()
        ->and($byKey['resume']['required'])->toBeTrue()
        ->and($byKey['resume']['type'])->toBe('file');

    $types = collect($schema->questions)->pluck('type')->all();
    foreach (['free_text', 'boolean', 'numeric', 'date', 'dropdown', 'multiple_choice', 'file'] as $t) {
        expect($types)->toContain($t);
    }

    $drop = collect($schema->questions)->firstWhere('type', 'dropdown');
    expect($drop->choices[0])->toHaveKeys(['id', 'body']);
    $mc = collect($schema->questions)->firstWhere('type', 'multiple_choice');
    expect($mc->singleAnswer)->toBeBool();
});
