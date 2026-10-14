<?php

use App\Enums\OrganizationRole;
use App\Models\OrganizationMember;
use App\Models\Scopes\OrganizationScope;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\HtmlString;

/**
 * @param  array<string, string>  $query
 */
function sidebarNavigationForRoute(string $role, string $routeName, array $query = []): DOMXPath
{
    $user = User::factory()->{$role}()->make();
    $request = Request::create('/sidebar-navigation-test', 'GET', $query);
    $route = new Route('GET', 'sidebar-navigation-test', static fn (): null => null);
    $route->name($routeName);
    $request->setRouteResolver(static fn (): Route => $route);
    $originalRequest = app('request');
    $originalUser = auth()->user();

    try {
        app()->instance('request', $request);
        auth()->setUser($user);

        $html = view('components.layout', ['slot' => new HtmlString('')])->render();
    } finally {
        app()->instance('request', $originalRequest);

        if ($originalUser !== null) {
            auth()->setUser($originalUser);
        } else {
            auth()->forgetUser();
        }
    }

    return sidebarNavigationDocument($html);
}

function sidebarNavigationDocument(string $html): DOMXPath
{
    $document = new DOMDocument;
    $previousErrorHandling = libxml_use_internal_errors(true);

    try {
        $document->loadHTML($html);
    } finally {
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrorHandling);
    }

    return new DOMXPath($document);
}

function assertSidebarNavigationSelection(DOMXPath $sidebar, string $label, string $icon): void
{
    $currentLinks = $sidebar->query('//nav[@aria-label="Sidebar navigation"]//a[@aria-current="page"]');

    expect($currentLinks->length)->toBe(1);

    $currentLink = $currentLinks->item(0);

    expect(trim($currentLink->textContent))->toBe($label)
        ->and($currentLink->getAttribute('class'))->toMatch('/(?:^|\s)menu-(?:dropdown-)?item-active(?:\s|$)/')
        ->and($currentLink->getAttribute('class'))->not->toMatch('/(?:^|\s)menu-(?:dropdown-)?item-inactive(?:\s|$)/')
        ->and($sidebar->query('.//svg[@data-lucide="'.$icon.'" and contains(@class, "menu-item-icon-active")]', $currentLink)->length)->toBe(1)
        ->and($sidebar->query('//nav[@aria-label="Sidebar navigation"]//a[not(@aria-current)]//svg[contains(@class, "menu-item-icon-active")]')->length)->toBe(0);
}

test('each dashboard renders the sidebar with its active Dashboard icon', function (string $role) {
    $user = User::factory()->{$role}()->create();
    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $sidebar = sidebarNavigationDocument($response->getContent());

    expect($sidebar->query('//nav[@aria-label="Sidebar navigation"]')->length)->toBe(1);

    assertSidebarNavigationSelection($sidebar, 'Dashboard', 'layout-dashboard');
})->with(['admin', 'employer', 'applicant']);

test('every sidebar navigation control uses a Lucide outline icon', function (string $role) {
    $sidebar = sidebarNavigationForRoute($role, 'dashboard');
    $controls = $sidebar->query('//nav[@aria-label="Sidebar navigation"]//a | //nav[@aria-label="Sidebar navigation"]//button');

    expect($sidebar->query('//nav[@aria-label="Sidebar navigation"]')->length)->toBe(1)
        ->and($controls->length)->toBeGreaterThan(5)
        ->and($sidebar->query('//nav//i | //nav//img')->length)->toBe(0);

    foreach ($controls as $control) {
        $icons = $sidebar->query('.//svg[@data-lucide and contains(@class, "menu-item-icon-")]', $control);

        expect($icons->length)->toBe(1)
            ->and($control->getAttribute('aria-label'))->toBe(trim($control->textContent))
            ->and($control->getAttribute('title'))->toBe(trim($control->textContent));

        $icon = $icons->item(0);

        expect($icon->getAttribute('stroke'))->toBe('currentColor')
            ->and($icon->getAttribute('fill'))->toBe('none')
            ->and($icon->getAttribute('class'))->toContain('lucide')
            ->and($icon->getAttribute('aria-hidden'))->toBe('true');
    }

    foreach ($sidebar->query('//nav//svg[@data-lucide]') as $icon) {
        $references = $sidebar->query('./use', $icon);

        expect($references->length)->toBe(1);

        $symbolId = 'sidebar-icon-'.$icon->getAttribute('data-lucide');
        $symbols = $sidebar->query('//symbol[@id="'.$symbolId.'"]');

        expect($references->item(0)->getAttribute('href'))->toBe('#'.$symbolId)
            ->and($symbols->length)->toBe(1);

        expect($sidebar->query('./path | ./rect | ./circle | ./polyline | ./line | ./ellipse | ./polygon', $symbols->item(0))->length)
            ->toBeGreaterThan(0);
    }
})->with(['admin', 'employer', 'applicant']);

test('sidebar highlights the current page and its matching icon on direct visits', function (
    string $role,
    string $routeName,
    string $label,
    string $icon,
) {
    assertSidebarNavigationSelection(sidebarNavigationForRoute($role, $routeName), $label, $icon);
})->with([
    'admin dashboard' => ['admin', 'dashboard', 'Dashboard', 'layout-dashboard'],
    'employer dashboard' => ['employer', 'dashboard', 'Dashboard', 'layout-dashboard'],
    'applicant dashboard' => ['applicant', 'dashboard', 'Dashboard', 'layout-dashboard'],
    'admin profile' => ['admin', 'profile.edit', 'User Profile', 'circle-user-round'],
    'employer profile' => ['employer', 'profile.edit', 'User Profile', 'circle-user-round'],
    'applicant profile' => ['applicant', 'profile.edit', 'User Profile', 'circle-user-round'],
    'employer profile alias' => ['employer', 'employer.profile', 'User Profile', 'circle-user-round'],
    'applicant profile alias' => ['applicant', 'client.profile', 'User Profile', 'circle-user-round'],
    'admin applicants' => ['admin', 'applicants', 'Applicants', 'user-round'],
    'admin applicant detail' => ['admin', 'applicants.profile.show', 'Applicants', 'user-round'],
    'admin employers' => ['admin', 'Employers', 'Employers', 'building-2'],
    'admin administrators' => ['admin', 'administrators', 'Administrators', 'shield-check'],
    'admin suspended accounts' => ['admin', 'suspended-accounts', 'Suspended Accounts', 'user-round-x'],
    'admin jobs' => ['admin', 'admin.jobs.index', 'All Jobs', 'briefcase-business'],
    'admin job detail' => ['admin', 'admin.jobs.show', 'All Jobs', 'briefcase-business'],
    'admin suspended jobs' => ['admin', 'suspended-jobs', 'Suspended Jobs', 'circle-x'],
    'admin open jobs' => ['admin', 'open-jobs', 'Open Jobs', 'briefcase-business'],
    'admin closed jobs' => ['admin', 'closed-jobs', 'Closed Jobs', 'circle-x'],
    'admin assessments' => ['admin', 'assessment-templates', 'Assessment Templates', 'clipboard-check'],
    'admin interviews' => ['admin', 'interview-templates', 'Interview Templates', 'message-square-text'],
    'admin emails' => ['admin', 'email-templates', 'Email Templates', 'mail'],
    'admin reports' => ['admin', 'Reports', 'Reports', 'chart-no-axes-combined'],
    'admin general settings' => ['admin', 'general-settings', 'General Settings', 'settings-2'],
    'admin email configuration' => ['admin', 'email-configuration', 'Email Configuration', 'mail'],
    'admin permissions' => ['admin', 'permission-management', 'Permission Management', 'shield-check'],
    'admin notifications' => ['admin', 'notifications', 'Notifications', 'bell'],
    'employer jobs' => ['employer', 'jobs', 'Manage Jobs', 'briefcase-business'],
    'employer job detail' => ['employer', 'jobs.show', 'Manage Jobs', 'briefcase-business'],
    'employer notifications' => ['employer', 'employer.notifications', 'Notifications', 'bell'],
    'applicant applications' => ['applicant', 'Client.Application', 'Applications', 'file-check-2'],
    'applicant applications alias' => ['applicant', 'client.jobs', 'Applications', 'file-check-2'],
    'applicant application detail' => ['applicant', 'client.applications.show', 'Applications', 'file-check-2'],
    'applicant new application' => ['applicant', 'applications.create', 'Applications', 'file-check-2'],
    'applicant jobs' => ['applicant', 'client.jobs-listings', 'Jobs', 'briefcase-business'],
    'applicant job detail' => ['applicant', 'job-details', 'Jobs', 'briefcase-business'],
    'applicant settings' => ['applicant', 'client.settings', 'Settings', 'settings-2'],
    'applicant notifications' => ['applicant', 'client.notifications', 'Notifications', 'bell'],
]);

test('employer candidate stage highlights exactly the selected filter', function (array $query, string $label, string $icon) {
    $sidebar = sidebarNavigationForRoute('employer', 'employer.Applied-Candidates', $query);

    assertSidebarNavigationSelection($sidebar, $label, $icon);

    expect($sidebar->query('//button[@aria-controls="candidates" and @aria-expanded="true"]')->length)->toBe(1);
})->with([
    'all candidates' => [[], 'Applied Candidates', 'users-round'],
    'empty stage' => [['stage' => ''], 'Applied Candidates', 'users-round'],
    'shortlisted' => [['stage' => 'shortlisted'], 'shortlisted Candidates', 'list-checks'],
    'interview' => [['stage' => 'interview'], 'Interviewed Candidates', 'calendar-days'],
    'selected' => [['stage' => 'selected'], 'Selected Candidates', 'user-check'],
    'rejected' => [['stage' => 'rejected'], 'Rejected Candidates', 'user-round-x'],
]);

test('employer navigation uses account settings and excludes admin-only tools', function () {
    $sidebar = sidebarNavigationForRoute('employer', 'profile.edit');
    $accountSettings = $sidebar->query('//nav//a[@aria-label="Account Settings"]');

    expect($accountSettings->length)->toBe(1)
        ->and(parse_url($accountSettings->item(0)->getAttribute('href'), PHP_URL_PATH))->toBe(route('profile.edit', [], false))
        ->and($accountSettings->item(0)->hasAttribute('aria-current'))->toBeFalse()
        ->and($sidebar->query('//nav//a[@aria-label="Assessment Templates" or @aria-label="Interview Templates" or @aria-label="Email Templates" or @aria-label="Reports" or @aria-label="Permission Management"]')->length)->toBe(0);

    assertSidebarNavigationSelection($sidebar, 'User Profile', 'circle-user-round');
});

test('sidebar links keep role-specific destinations', function (string $role, array $destinations) {
    $sidebar = sidebarNavigationForRoute($role, 'dashboard');

    foreach ($destinations as $label => $destination) {
        $links = $sidebar->query('//nav[@aria-label="Sidebar navigation"]//a[@aria-label="'.$label.'"]');

        expect($links->length)->toBe(1)
            ->and(parse_url($links->item(0)->getAttribute('href'), PHP_URL_PATH))->toBe(route($destination, [], false));
    }
})->with([
    'admin' => ['admin', ['All Jobs' => 'admin.jobs.index', 'Applicants' => 'applicants', 'Notifications' => 'notifications']],
    'employer' => ['employer', ['Manage Jobs' => 'jobs', 'Applied Candidates' => 'employer.Applied-Candidates', 'Notifications' => 'employer.notifications']],
    'applicant' => ['applicant', ['Applications' => 'Client.Application', 'Jobs' => 'client.jobs-listings', 'Notifications' => 'client.notifications']],
]);

test('candidate filter links retain their pipeline query parameter', function (string $label, string $stage) {
    $sidebar = sidebarNavigationForRoute('employer', 'dashboard');
    $links = $sidebar->query('//nav//a[@aria-label="'.$label.'"]');

    expect($links->length)->toBe(1)
        ->and(parse_url($links->item(0)->getAttribute('href'), PHP_URL_PATH))->toBe(route('employer.Applied-Candidates', [], false))
        ->and(parse_url($links->item(0)->getAttribute('href'), PHP_URL_QUERY))->toBe(http_build_query(['stage' => $stage]));
})->with([
    ['shortlisted Candidates', 'shortlisted'],
    ['Interviewed Candidates', 'interview'],
    ['Selected Candidates', 'selected'],
    ['Rejected Candidates', 'rejected'],
]);

test('the hiring team navigation link is visible only to team managers', function () {
    $owner = User::factory()->employer()->create();
    $response = $this->actingAs($owner)->get(route('employer.team.index'))->assertOk();
    $sidebar = sidebarNavigationDocument($response->getContent());

    assertSidebarNavigationSelection($sidebar, 'Hiring Team', 'users-round');

    $organizationId = session('active_organization_id');
    OrganizationMember::withoutGlobalScope(OrganizationScope::class)
        ->where('user_id', $owner->id)->where('organization_id', $organizationId)
        ->update(['role' => OrganizationRole::Recruiter]);

    $sidebar = sidebarNavigationDocument($this->get(route('jobs'))->assertOk()->getContent());

    expect($sidebar->query('//nav//a[@aria-label="Hiring Team"]')->length)->toBe(0);
});

test('sidebar opens the active navigation group on initial render', function (string $role, string $routeName, string $groupId) {
    $sidebar = sidebarNavigationForRoute($role, $routeName);
    $activeGroups = $sidebar->query('//nav//button[@aria-expanded="true"]');

    expect($activeGroups->length)->toBe(1);

    $activeGroup = $activeGroups->item(0);
    $openPanels = $sidebar->query('//nav//*[@data-open="true"]');

    expect($openPanels->length)->toBe(1);

    $openPanel = $openPanels->item(0);

    expect($activeGroup->getAttribute('aria-controls'))->toBe($groupId)
        ->and($sidebar->query('.//svg[contains(@class, "menu-item-icon-active")]', $activeGroup)->length)->toBe(1)
        ->and($openPanel->getAttribute('id'))->toBe($groupId)
        ->and($openPanel->getAttribute('aria-hidden'))->toBe('false')
        ->and($openPanel->hasAttribute('inert'))->toBeFalse()
        ->and($sidebar->query('//nav//button[@aria-expanded="false"]//svg[contains(@class, "menu-item-icon-active")]')->length)->toBe(0);
})->with([
    'user management' => ['admin', 'Employers', 'user-management'],
    'job management detail' => ['admin', 'admin.jobs.show', 'job-management'],
    'recruitment tools' => ['admin', 'interview-templates', 'recruitment-tools'],
    'admin settings' => ['admin', 'general-settings', 'settings'],
    'candidates' => ['employer', 'employer.Applied-Candidates', 'candidates'],
    'candidate detail' => ['employer', 'employer.applications.show', 'candidates'],
]);

test('sidebar accordion controls identify panels and disable collapsed children', function (string $role, string $routeName) {
    $sidebar = sidebarNavigationForRoute($role, $routeName);
    $buttons = $sidebar->query('//nav[@aria-label="Sidebar navigation"]//button[@aria-controls]');

    expect($buttons->length)->toBeGreaterThan(0)
        ->and($sidebar->query('//nav[@aria-label="Sidebar navigation" and @x-data]')->length)->toBe(1)
        ->and($sidebar->query('//nav[@aria-label="Sidebar navigation"]//*[@x-data]')->length)->toBe(0)
        ->and($sidebar->query('//nav[@aria-label="Sidebar navigation"]//*[@data-open="true"]')->length)->toBeLessThanOrEqual(1);

    foreach ($buttons as $button) {
        $panels = $sidebar->query('//*[@id="'.$button->getAttribute('aria-controls').'"]');

        expect($panels->length)->toBe(1);

        $panel = $panels->item(0);
        $isExpanded = $button->getAttribute('aria-expanded') === 'true';

        expect($panel->getAttribute('class'))->toContain('sidebar-submenu')
            ->and($panel->getAttribute('data-open'))->toBe($isExpanded ? 'true' : 'false')
            ->and($panel->getAttribute('aria-hidden'))->toBe($isExpanded ? 'false' : 'true')
            ->and($panel->hasAttribute('inert'))->toBe(! $isExpanded);
    }
})->with([
    'admin with closed groups' => ['admin', 'dashboard'],
    'admin with an active group' => ['admin', 'Employers'],
    'employer with closed groups' => ['employer', 'dashboard'],
    'employer with an active group' => ['employer', 'employer.Applied-Candidates'],
]);

test('sidebar has one scrolling navigation region and a linked layout toggle', function (string $role) {
    $sidebar = sidebarNavigationForRoute($role, 'dashboard');
    $panels = $sidebar->query('//aside[@id="application-sidebar"]');

    expect($panels->length)->toBe(1);

    $panel = $panels->item(0);

    expect($panel->getAttribute('class'))->toContain('overflow-hidden')
        ->and($panel->getAttribute('class'))->not->toContain('overflow-y-auto')
        ->and($sidebar->query('.//*[contains(concat(" ", normalize-space(@class), " "), " sidebar-navigation-scroll ")]', $panel)->length)->toBe(1)
        ->and($sidebar->query('.//*[contains(concat(" ", normalize-space(@class), " "), " overflow-y-auto ")]', $panel)->length)->toBe(0)
        ->and($sidebar->query('//button[@aria-controls="application-sidebar"]')->length)->toBe(1);
})->with(['admin', 'employer', 'applicant']);

test('sidebar selection follows each request without retaining a previous page', function (string $role) {
    assertSidebarNavigationSelection(sidebarNavigationForRoute($role, 'dashboard'), 'Dashboard', 'layout-dashboard');
    assertSidebarNavigationSelection(sidebarNavigationForRoute($role, 'profile.edit'), 'User Profile', 'circle-user-round');
    assertSidebarNavigationSelection(sidebarNavigationForRoute($role, 'dashboard'), 'Dashboard', 'layout-dashboard');

    $sidebar = sidebarNavigationForRoute($role, 'home');

    expect($sidebar->query('//nav//a[@aria-current]')->length)->toBe(0)
        ->and($sidebar->query('//nav//button[@aria-expanded="true"]')->length)->toBe(0)
        ->and($sidebar->query('//*[@x-data and contains(@x-data, "$persist")]')->length)->toBe(0);
})->with(['admin', 'employer', 'applicant']);

test('candidate detail keeps its parent active without selecting an unrelated stage', function () {
    $sidebar = sidebarNavigationForRoute('employer', 'employer.applications.show', ['stage' => 'rejected']);

    expect($sidebar->query('//nav//a[@aria-current]')->length)->toBe(0)
        ->and($sidebar->query('//button[@aria-controls="candidates" and @aria-expanded="true"]')->length)->toBe(1);
});

test('dashboard layouts allow mobile zoom for every role', function (string $role) {
    $document = sidebarNavigationForRoute($role, 'dashboard');
    $viewport = $document->query('//meta[@name="viewport"]')->item(0);

    expect($viewport->getAttribute('content'))->toBe('width=device-width, initial-scale=1.0');
})->with(['admin', 'employer', 'applicant']);
