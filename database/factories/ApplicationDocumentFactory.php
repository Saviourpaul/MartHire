<?php

namespace Database\Factories;

use App\Enums\ApplicationDocumentType;
use App\Models\ApplicationDocument;
use App\Models\ApplicationForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApplicationDocument>
 */
class ApplicationDocumentFactory extends Factory
{
    protected $model = ApplicationDocument::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement([
            ...ApplicationDocumentType::identityTypes(),
            ApplicationDocumentType::Education,
        ]);

        return [
            'application_form_id' => ApplicationForm::factory(),
            'document_type' => $type,
            'document_name' => $type->label(),
            'file_path' => 'application-documents/sample.pdf',
            'original_name' => fake()->word().'.pdf',
            'mime_type' => 'application/pdf',
            'size' => fake()->numberBetween(10000, 2000000),
        ];
    }

    public function type(ApplicationDocumentType $type): static
    {
        return $this->state(fn (array $attributes) => [
            'document_type' => $type,
            'document_name' => $type->label(),
        ]);
    }
}
