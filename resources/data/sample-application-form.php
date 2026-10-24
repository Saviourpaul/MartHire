<?php

/*
 * Placeholder application form while the Workable connection is not set up. Everything here is made up.
 * To go live, pass the real form for the job to the `Apply` view as `$form` with the same keys and this
 * file is no longer read. It mirrors what Workable gives you: the standard fields plus the job's own
 * questions (GET .../jobs/{shortcode}/questions).
 *
 *   fields:    standard details. Each: key, label, type (text|email|tel|textarea|file), required,
 *              section (details|documents), hint?, autocomplete?, max_length?,
 *              accept? (file extensions), max_file_size? (bytes)
 *   questions: the job's own questions. Each: id, body, type (free_text|short_text|boolean|multiple_choice|
 *              dropdown|numeric|date|file), required, hint?, single_answer? and choices? [id, body] for
 *              multiple_choice/dropdown, supported_file_types? and max_file_size? for file
 *
 * ":company" in any label, body or hint is replaced with the employer's name.
 *
 * Submitting is the backend's job: define a POST route named `jobs.apply.store` (see routes/web.php) and the
 * preview notice goes away. It should send the answers to Workable (POST .../jobs/{shortcode}/candidates with
 * a token that has the w_candidates scope), turn Workable's "candidate already exists" reply into an error on
 * `email`, and redirect back with ->with('applied', true) to show the confirmation.
 */

return [
    'fields' => [
        ['key' => 'firstname', 'label' => 'First name', 'type' => 'text', 'required' => true, 'section' => 'details', 'autocomplete' => 'given-name'],
        ['key' => 'lastname', 'label' => 'Last name', 'type' => 'text', 'required' => true, 'section' => 'details', 'autocomplete' => 'family-name'],
        ['key' => 'email', 'label' => 'Email address', 'type' => 'email', 'required' => true, 'section' => 'details', 'autocomplete' => 'email', 'hint' => ':company will use this to reply to you.'],
        ['key' => 'phone', 'label' => 'Phone number', 'type' => 'tel', 'required' => false, 'section' => 'details', 'autocomplete' => 'tel', 'hint' => 'Include your country code if you are applying from abroad.'],
        ['key' => 'address', 'label' => 'Where are you based?', 'type' => 'text', 'required' => false, 'section' => 'details', 'autocomplete' => 'address-level2', 'hint' => 'City and country.'],
        ['key' => 'headline', 'label' => 'Current role', 'type' => 'text', 'required' => false, 'section' => 'details', 'max_length' => 120, 'hint' => 'For example, Product designer at a small studio.'],
        ['key' => 'resume', 'label' => 'CV or résumé', 'type' => 'file', 'required' => true, 'section' => 'documents', 'accept' => ['pdf', 'doc', 'docx', 'rtf'], 'max_file_size' => 5 * 1024 * 1024, 'hint' => 'PDF, DOC, DOCX or RTF, up to 5 MB.'],
        ['key' => 'cover_letter', 'label' => 'Cover letter', 'type' => 'textarea', 'required' => false, 'section' => 'documents', 'max_length' => 3000, 'hint' => 'A few lines on why you would be a good fit.'],
    ],

    'questions' => [
        ['id' => 'q-why', 'body' => 'Why would you like to work at :company?', 'type' => 'free_text', 'required' => true, 'hint' => 'A few sentences is plenty.'],
        ['id' => 'q-right', 'body' => 'Are you legally able to work in the country this role is based in?', 'type' => 'boolean', 'required' => true],
        ['id' => 'q-tools', 'body' => 'Which of these have you used at work?', 'type' => 'multiple_choice', 'required' => false, 'single_answer' => false, 'choices' => [
            ['id' => 'c-1', 'body' => 'Figma'],
            ['id' => 'c-2', 'body' => 'Sketch'],
            ['id' => 'c-3', 'body' => 'Adobe XD'],
            ['id' => 'c-4', 'body' => 'None of these'],
        ]],
        ['id' => 'q-years', 'body' => 'How many years of experience do you have in this field?', 'type' => 'numeric', 'required' => false],
        ['id' => 'q-start', 'body' => 'When could you start?', 'type' => 'date', 'required' => false],
        ['id' => 'q-source', 'body' => 'How did you hear about this role?', 'type' => 'dropdown', 'required' => false, 'choices' => [
            ['id' => 's-1', 'body' => 'NearbyWorks'],
            ['id' => 's-2', 'body' => 'A friend or colleague'],
            ['id' => 's-3', 'body' => 'Social media'],
            ['id' => 's-4', 'body' => 'Somewhere else'],
        ]],
        ['id' => 'q-portfolio', 'body' => 'Portfolio or work samples', 'type' => 'file', 'required' => false, 'supported_file_types' => ['pdf'], 'max_file_size' => 10 * 1024 * 1024, 'hint' => 'PDF, up to 10 MB.'],
    ],
];
