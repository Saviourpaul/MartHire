import { readFileSync } from 'node:fs';
import { resolve } from 'node:path';
import { expect, test } from '@playwright/test';

const root = process.cwd();
const manifest = JSON.parse(readFileSync(resolve(root, 'public/asset/marthire/build/manifest.json'), 'utf8'));
const appScriptPath = `/asset/marthire/build/${manifest['resources/js/app.js'].file}`;
const stylePaths = [
    ...manifest['resources/js/app.js'].css,
    manifest['resources/css/app.css'].file,
].map((file) => `/asset/marthire/build/${file}`);
const appScript = readFileSync(resolve(root, 'public/asset/marthire/build', manifest['resources/js/app.js'].file));
const styles = stylePaths.map((path) => ({
    path,
    body: readFileSync(resolve(root, 'public', path.slice(1))),
}));

const applicationPage = `<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    ${stylePaths.map((path) => `<link rel="stylesheet" href="${path}">`).join('\n')}
</head>
<body>
    <main class="nw-container nw-native-apply">
        <h1>Apply for Product Designer</h1>
        <div class="nw-wizard" x-data="nativeApplicationForm({ location: { countries: [], countryCode: '', state: '', city: '', lga: '', states: [], cities: [], statesUrl: '/states', citiesUrl: '/cities', nigeriaUsesLga: true } })">
            <div class="nw-error-summary" x-ref="summary" tabindex="-1" role="alert" x-show="summary.length" x-cloak></div>
            <div class="nw-error-summary" x-ref="submissionError" tabindex="-1" role="alert" x-show="submissionError" x-text="submissionError" x-cloak></div>
            <form method="POST" action="/apply" enctype="multipart/form-data" novalidate
                data-max-total-upload-bytes="68157440" data-max-single-upload-bytes="5242880" data-max-upload-files="20"
                class="nw-wizard__form" x-ref="form" x-on:submit="submit($event)">
                <input type="hidden" name="_token" value="browser-check-token">
                <fieldset class="nw-form-section nw-wizard__step" data-wizard-step data-step-title="Your details">
                    <div class="nw-field" data-field="name" data-type="text" data-label="Name" data-required>
                        <label for="name">Name</label><input id="name" name="name" type="text" required>
                    </div>
                    <div class="nw-field" data-field="resume" data-type="file" data-label="Resume" data-accept="pdf" data-max="5242880">
                        <label for="resume">Resume</label><input id="resume" name="resume" type="file" accept=".pdf" x-on:change="pick($event)">
                    </div>
                </fieldset>
                <fieldset class="nw-form-section nw-wizard__step" data-wizard-step data-step-title="Review and send">
                    <div class="nw-field" data-field="confirm" data-type="boolean" data-label="Confirmation" data-required data-must-accept>
                        <label><input name="confirm" type="checkbox" value="yes"> I confirm the details are accurate.</label>
                    </div>
                </fieldset>
                <div class="nw-wizard-actions">
                    <div class="nw-wizard-actions__progress" x-ref="progressStatus" x-show="submitting" x-cloak role="status" aria-live="polite"
                        data-progress-preparing="Preparing your application…"
                        data-progress-uploading="Uploading your application…"
                        data-progress-finishing="Files uploaded. Finishing your submission…">
                        <span x-text="submissionProgressLabel()">Preparing your application…</span>
                        <progress x-show="uploadProgress === null" max="100" aria-label="Application upload progress"></progress>
                        <progress x-show="uploadProgress !== null" max="100" x-bind:value="uploadProgress" aria-label="Application upload progress"></progress>
                    </div>
                    <button type="button" class="nw-button nw-button--primary" x-show="ready && !isLastStep" x-cloak x-on:click="next()">Next</button>
                    <button type="submit" class="nw-button nw-button--primary" x-show="!ready || isLastStep" x-bind:disabled="submitting">Submit application</button>
                </div>
            </form>
        </div>
    </main>
    <script type="module" src="${appScriptPath}"></script>
</body>
</html>`;

for (const viewport of [
    { name: 'desktop', size: { width: 1440, height: 900 } },
    { name: 'mobile', size: { width: 390, height: 844 } },
]) {
    test(`native application submission completes in Playwright Chromium at ${viewport.name} size`, async ({ page }) => {
        await page.setViewportSize(viewport.size);

        let postCount = 0;
        let releaseSubmission;
        const submissionGate = new Promise((resolveSubmission) => { releaseSubmission = resolveSubmission; });
        let requestReceived;
        const requestStarted = new Promise((resolveRequest) => { requestReceived = resolveRequest; });

        await page.route('**/*', async (route) => {
            const request = route.request();
            const url = new URL(request.url());

            if (url.pathname === '/apply' && request.method() === 'GET') {
                await route.fulfill({ status: 200, contentType: 'text/html; charset=utf-8', body: applicationPage });
                return;
            }

            if (url.pathname === '/apply' && request.method() === 'POST') {
                postCount += 1;
                expect(request.headers()['content-type']).toContain('multipart/form-data');
                expect(request.postDataBuffer()?.byteLength ?? 0).toBeGreaterThan(0);
                requestReceived();
                await submissionGate;
                await route.fulfill({
                    status: 201,
                    contentType: 'application/json; charset=utf-8',
                    body: JSON.stringify({ submitted: true, redirect: '/applications/receipt' }),
                });
                return;
            }

            if (url.pathname === '/applications/receipt') {
                await route.fulfill({ status: 200, contentType: 'text/html; charset=utf-8', body: '<h1>Application received</h1>' });
                return;
            }

            const style = styles.find((asset) => asset.path === url.pathname);
            if (style) {
                await route.fulfill({ status: 200, contentType: 'text/css; charset=utf-8', body: style.body });
                return;
            }

            if (url.pathname === appScriptPath) {
                await route.fulfill({ status: 200, contentType: 'application/javascript; charset=utf-8', body: appScript });
                return;
            }

            await route.fulfill({ status: 204, body: '' });
        });

        try {
            await page.goto('https://nearbyworks.test/apply');
            await expect(page.getByRole('heading', { name: 'Apply for Product Designer' })).toBeVisible();
            await page.getByLabel('Name').fill('Alex Applicant');
            await page.getByLabel('Resume').setInputFiles({
                name: 'resume.pdf',
                mimeType: 'application/pdf',
                buffer: Buffer.from('%PDF-1.4 browser upload verification'),
            });
            await page.getByRole('button', { name: 'Next' }).click();
            await page.getByLabel('I confirm the details are accurate.').check();
            await page.getByRole('button', { name: 'Submit application' }).click();

            await requestStarted;
            await expect(page.getByRole('status')).toBeVisible();
            expect(postCount).toBe(1);
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);

            await page.locator('form').evaluate((form) => form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true })));
            expect(postCount).toBe(1);

            releaseSubmission();
            await expect(page.getByRole('heading', { name: 'Application received' })).toBeVisible();
        } finally {
            releaseSubmission();
        }
    });
}
