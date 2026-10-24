<?php

namespace App\Actions\Workable;

use App\Integrations\Workable\DTO\ApplicationSchema;
use App\Integrations\Workable\DTO\QuestionData;
use App\Integrations\Workable\WorkableClient;
use App\Models\Company;
use App\Models\JobPosting;
use Illuminate\Support\Facades\Cache;

/**
 * Fetches and caches a posting's Workable application form, and records whether
 * we can build a correct native form for it (design §7.1). If any REQUIRED
 * field/question has a type we don't support, apply_supported is set false so
 * the posting falls back to redirect-apply — we never submit an application we
 * know is incomplete.
 */
class ResolveApplicationSchema
{
    /** Question types we can render and submit. */
    private const SUPPORTED_QUESTION_TYPES = ['free_text', 'short_text', 'multiple_choice', 'boolean', 'dropdown', 'numeric', 'date', 'file'];

    /** Standard form-field types we can render ('complex' is not supported in v1). */
    private const SUPPORTED_FIELD_TYPES = ['string', 'free_text', 'file', 'boolean', 'date'];

    public function __construct(private WorkableClient $client) {}

    public function __invoke(Company $company, JobPosting $posting): ApplicationSchema
    {
        $minutes = (int) config('workable.schema_cache_minutes', 15);

        /** @var ApplicationSchema $schema */
        $schema = Cache::remember(
            "workable:schema:{$posting->id}",
            now()->addMinutes($minutes),
            fn () => $this->client->getApplicationForm($company, $posting->shortcode),
        );

        $supported = $this->isSupported($schema);
        if ($posting->apply_supported !== $supported) {
            $posting->forceFill(['apply_supported' => $supported])->save();
        }

        return $schema;
    }

    public function isSupported(ApplicationSchema $schema): bool
    {
        foreach ($schema->formFields as $field) {
            if (($field['required'] ?? false) && ! in_array($field['type'] ?? '', self::SUPPORTED_FIELD_TYPES, true)) {
                return false;
            }
        }

        foreach ($schema->questions as $question) {
            /** @var QuestionData $question */
            if ($question->required && ! in_array($question->type, self::SUPPORTED_QUESTION_TYPES, true)) {
                return false;
            }
        }

        return true;
    }
}
