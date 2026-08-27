<?php

use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;

it('returns a successful response', function () {
    $response = $this->get('/');

    $response->assertStatus(200);
    $response->assertSee('MartHire');
    $response->assertSee('Latest Job Openings');
});

it('returns a successful response for public jobs page', function () {
    $response = $this->get(route('jobs.public'));

    $response->assertStatus(200);
    $response->assertSee('All Jobs');
});

it('returns a successful response for the about page', function () {
    $response = $this->get(route('about'));

    $response->assertStatus(200);
    $response->assertSee('Empowering Recruitment for a Modern Workforce');
});

it('returns a successful response for the services page', function () {
    $response = $this->get(route('services'));

    $response->assertStatus(200);
    $response->assertSee('Recruitment Solutions for Every Sector');
    $response->assertSee('Government & Public Sector Recruitment', false);
});

it('returns a successful response for the features page', function () {
    $response = $this->get(route('features'));

    $response->assertStatus(200);
    $response->assertSee('Everything You Need for Professional Recruitment');
});

it('returns a successful response for the faq page', function () {
    $response = $this->get(route('faq'));

    $response->assertStatus(200);
    $response->assertSee('How Can We Help?');
    $response->assertSee('Job Seekers');
});

it('returns a successful response for the contact page', function () {
    $response = $this->get(route('contact'));

    $response->assertStatus(200);
    $response->assertSee('Send Us a Message');
    $response->assertSee('support@nexhire.com');
});

it('accepts valid contact form submissions', function () {
    $response = $this->withoutMiddleware(ValidateCsrfToken::class)->post(route('contact.store'), [
        'name' => 'Jane Applicant',
        'email' => 'jane@example.com',
        'phone' => '+2348000000001',
        'subject' => 'Employer onboarding',
        'inquiry_type' => 'employer',
        'message' => 'We would like to discuss posting vacancies on MartHire.',
    ]);

    $response->assertRedirect(route('contact'));
    $response->assertSessionHas('success');
});

it('validates contact form submissions', function () {
    $response = $this->withoutMiddleware(ValidateCsrfToken::class)->post(route('contact.store'), []);

    $response->assertSessionHasErrors(['name', 'email', 'subject', 'inquiry_type', 'message']);
});

it('renders updated navigation links on public pages', function () {
    $response = $this->get(route('home'));

    $response->assertSee(route('services'), false);
    $response->assertSee(route('features'), false);
    $response->assertSee(route('faq'), false);
    $response->assertSee(route('contact'), false);
});

it('returns the custom 404 page for unknown routes', function () {
    $response = $this->get('/unknown-route');

    $response->assertStatus(404);
    $response->assertViewIs('404');
    $response->assertSee('Page not found');
    $response->assertSee(route('home'), false);
});

it('renders the 404 back link from the previous page', function () {
    $previousUrl = url('/find-jobs');

    $response = $this->withHeaders([
        'referer' => $previousUrl,
    ])->get('/unknown-route');

    $response->assertStatus(404);
    $response->assertSee($previousUrl, false);
});
