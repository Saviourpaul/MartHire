<?php

use App\Actions\Workable\BuildApplicationValidator;
use App\Actions\Workable\PayloadBuilder;
use App\Actions\Workable\ResolveApplicationSchema;
use App\Integrations\Workable\DTO\ApplicationSchema;
use App\Integrations\Workable\WorkableClient;
use App\Models\Company;
use App\Models\JobApplication;
use App\Models\JobPosting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function applyFixtureSchema(): ApplicationSchema
{
    $form = json_decode(file_get_contents(base_path('tests/Fixtures/workable/application_form.json')), true);

    return ApplicationSchema::fromApi($form);
}

it('builds server-side validation rules from the schema', function () {
    $rules = (new BuildApplicationValidator)->rules(applyFixtureSchema());

    expect($rules['first_name'])->toContain('required');
    expect($rules['last_name'])->toContain('required');
    expect($rules['email'])->toContain('required')->toContain('email:rfc');
    expect($rules['resume'])->toContain('required')->toContain('file');          // fixture resume is required
    expect($rules['question_q_free'])->toContain('required')->toContain('string');
    expect($rules['question_q_bool'])->toContain('accepted');                    // required boolean
    expect($rules['question_q_num'])->toContain('nullable')->toContain('numeric');
    expect($rules['question_q_drop'])->toContain('in:c1,c2');
    expect($rules['question_q_mc'])->toContain('array');
    expect($rules['question_q_mc.*'])->toContain('in:s1,s2');
});

it('builds a create-candidate payload with sourced=false and type-correct answers', function () {
    $data = [
        'first_name' => 'Test', 'last_name' => 'Applicant', 'email' => 'test@example.com',
        'cover_letter' => 'Hello',
        'question_q_free' => 'Because I care', 'question_q_bool' => true,
        'question_q_drop' => 'c1', 'question_q_mc' => ['s1', 's2'],
    ];
    $files = ['resume' => ['name' => 'cv.pdf', 'data' => 'QkFTRTY0']];

    $payload = (new PayloadBuilder)->build(applyFixtureSchema(), $data, $files);

    expect($payload['sourced'])->toBeFalse();
    expect($payload['candidate']['firstname'])->toBe('Test');
    expect($payload['candidate']['lastname'])->toBe('Applicant');
    expect($payload['candidate']['email'])->toBe('test@example.com');
    expect($payload['candidate']['cover_letter'])->toBe('Hello');
    expect($payload['candidate']['resume'])->toBe(['name' => 'cv.pdf', 'data' => 'QkFTRTY0']);

    $answers = collect($payload['candidate']['answers'])->keyBy('question_key');
    expect($answers['q_free']['body'])->toBe('Because I care');
    expect($answers['q_bool']['checked'])->toBeTrue();
    expect($answers['q_drop']['choices'])->toBe(['c1']);
    expect($answers['q_mc']['choices'])->toBe(['s1', 's2']);
    // Unanswered optional questions are omitted.
    expect($answers->has('q_num'))->toBeFalse();
    expect($answers->has('q_file'))->toBeFalse();
});

it('marks a schema with a required unsupported field as not natively supported', function () {
    $resolver = new ResolveApplicationSchema(app(WorkableClient::class));

    expect($resolver->isSupported(applyFixtureSchema()))->toBeTrue();

    $complex = new ApplicationSchema(
        [['key' => 'addresses', 'label' => 'Addresses', 'type' => 'complex', 'required' => true]],
        [],
    );
    expect($resolver->isSupported($complex))->toBeFalse();
});

it('resolves, caches, and flags apply_supported for a posting', function () {
    Http::fake([
        '*/spi/v3/jobs/SENIO001/application_form*' => Http::response(
            json_decode(file_get_contents(base_path('tests/Fixtures/workable/application_form.json')), true),
            200,
        ),
    ]);

    $company = Company::factory()->create(['subdomain' => 'pilotco', 'api_token' => 'tok_123']);
    $posting = JobPosting::factory()->for($company)->create(['shortcode' => 'SENIO001', 'apply_supported' => null]);

    $schema = (new ResolveApplicationSchema(app(WorkableClient::class)))($company, $posting);

    expect($schema->questions)->toHaveCount(7);
    expect($posting->fresh()->apply_supported)->toBeTrue();
});

it('stores the application payload encrypted and blocks a duplicate email per posting', function () {
    $posting = JobPosting::factory()->create();

    $app = JobApplication::create([
        'job_posting_id' => $posting->id,
        'idempotency_key' => (string) Str::uuid(),
        'email' => 'dupe@example.com',
        'first_name' => 'A', 'last_name' => 'B',
        'status' => 'queued',
        'payload' => ['answers' => [['question_key' => 'q_free', 'body' => 'secret note']]],
    ]);

    $raw = DB::table('job_applications')->where('id', $app->id)->value('payload');
    expect($raw)->not->toContain('secret note');                 // encrypted at rest
    expect($app->fresh()->payload['answers'][0]['body'])->toBe('secret note');

    expect(fn () => JobApplication::create([
        'job_posting_id' => $posting->id,
        'idempotency_key' => (string) Str::uuid(),
        'email' => 'dupe@example.com', 'first_name' => 'C', 'last_name' => 'D', 'status' => 'queued',
    ]))->toThrow(\Illuminate\Database\QueryException::class);
});
