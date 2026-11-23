import base from './playwright.config.js';

export default {
    ...base,
    reporter: 'list',
    projects: [{
        name: 'Google Chrome',
        use: { ...base.projects[0].use, channel: 'chrome' },
    }],
};
