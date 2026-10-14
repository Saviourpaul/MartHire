const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

const layout = fs.readFileSync(`${__dirname}/../resources/views/components/layout.blade.php`, 'utf8');

function extract(pattern, description) {
    const match = layout.match(pattern);

    if (!match) {
        throw new Error(`Unable to read ${description} from the dashboard layout.`);
    }

    return match;
}

function attribute(markup, name) {
    const escapedName = name.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    const match = markup.match(new RegExp(`(?:^|\\s)${escapedName}="([^"]*)"`));

    if (!match) {
        throw new Error(`Unable to read the ${name} attribute from the dashboard layout.`);
    }

    return match[1];
}

const bodyState = extract(/<body\b[\s\S]*?\bx-data="([\s\S]*?)"\s+@resize/, 'sidebar state')[1];
const aside = extract(/<aside\b[^>]*\bid="application-sidebar"[^>]*>/, 'sidebar panel')[0];
const toggle = extract(/<button\b[^>]*\baria-controls="application-sidebar"[^>]*>/, 'sidebar toggle')[0];
const closeButton = extract(/<button\b[^>]*\baria-label="Close navigation"[^>]*>/, 'mobile close button')[0];
const backdrop = extract(/<div\b[^>]*class="[^"]*\bsidebar-backdrop\b[^"]*"[^>]*>/, 'sidebar backdrop')[0];
const content = extract(/<div\b[^>]*class="[^"]*\boverflow-x-hidden\s+overflow-y-auto\b[^"]*"[^>]*>/, 'content panel')[0];
const navigation = extract(/<nav\b(?:[^>"']|"[^"]*"|'[^']*')*\baria-label="Sidebar navigation"(?:[^>"']|"[^"]*"|'[^']*')*>/, 'sidebar navigation')[0];
const groupIds = ['user-management', 'job-management', 'recruitment-tools', 'candidates', 'settings'];

function groupExpression(groupId, tag, name) {
    const selector = tag === 'button' ? 'aria-controls' : 'id';
    const openingTags = layout.match(new RegExp(`<${tag}\\b(?:->|[^>"']|"[^"]*"|'[^']*')*>`, 'g')) ?? [];
    const matches = openingTags.filter((markup) => new RegExp(`(?:^|\\s)${selector}="${groupId}"`).test(markup));

    assert.equal(matches.length, 1, `Expected one literal ${groupId} accordion ${tag}.`);

    return attribute(matches[0], name);
}

function createSidebar(width, initialOpenGroup = null) {
    let viewportWidth = width;
    const callbacks = [];
    const storage = new Map();
    const classes = new Set();
    const focus = { trigger: 0, sidebarItem: 0 };
    const trigger = { focus: () => focus.trigger++ };
    const sidebarItem = { focus: () => focus.sidebarItem++ };
    const context = vm.createContext({
        window: {
            matchMedia: (query) => {
                const minimumWidth = Number(query.match(/min-width:\s*(\d+)px/)[1]);

                return { matches: viewportWidth >= minimumWidth };
            },
            localStorage: {
                getItem: (key) => storage.get(key) ?? null,
                setItem: (key, value) => storage.set(key, String(value)),
            },
        },
        document: {
            documentElement: {
                classList: {
                    toggle: (name, enabled) => enabled ? classes.add(name) : classes.delete(name),
                },
            },
        },
    });
    const state = vm.runInContext(`(${bodyState})`, context);

    Object.assign(state, {
        openGroup: initialOpenGroup,
        $el: trigger,
        $watch: () => {},
        $nextTick: (callback) => callbacks.push(callback),
        $refs: { sidebar: { querySelector: () => sidebarItem } },
    });

    context.scope = state;
    state.init();

    function execute(expression) {
        return vm.runInContext(`with (scope) { ${expression} }`, context);
    }

    function evaluate(expression) {
        return vm.runInContext(`with (scope) { (${expression}) }`, context);
    }

    function flushTicks() {
        while (callbacks.length > 0) {
            callbacks.shift()();
        }
    }

    return {
        state,
        focus,
        flushTicks,
        toggle: () => execute(attribute(toggle, '@click.stop')),
        close: () => execute(attribute(closeButton, '@click')),
        escape: () => execute(attribute(layout, '@keydown.escape.window')),
        backdropClick: () => execute(attribute(backdrop, '@click')),
        resize: (nextWidth) => {
            viewportWidth = nextWidth;
            execute(attribute(layout, '@resize.window.debounce.150ms'));
        },
        initializeGroup: (groupId) => {
            const originalElement = state.$el;
            state.$el = {
                querySelector: (selector) => {
                    assert.equal(selector, 'button[aria-expanded=true]');

                    return groupId === null ? null : {
                        getAttribute: (name) => {
                            assert.equal(name, 'aria-controls');

                            return groupId;
                        },
                    };
                },
            };

            try {
                execute(attribute(navigation, 'x-init'));
            } finally {
                state.$el = originalElement;
            }
        },
        clickGroup: (groupId) => execute(groupExpression(groupId, 'button', '@click')),
        group: (groupId) => ({
            expanded: evaluate(groupExpression(groupId, 'button', ':aria-expanded')),
            open: evaluate(groupExpression(groupId, 'div', ':data-open')),
            hidden: evaluate(groupExpression(groupId, 'div', ':aria-hidden')),
            inert: evaluate(groupExpression(groupId, 'div', ':inert')),
        }),
        accessibility: () => ({
            sidebarHidden: evaluate(attribute(aside, ':aria-hidden')),
            sidebarInert: evaluate(attribute(aside, ':inert')),
            contentInert: evaluate(attribute(content, ':inert')),
            backdropVisible: evaluate(attribute(backdrop, 'x-show')),
            toggleExpanded: evaluate(attribute(toggle, ':aria-expanded')),
            toggleLabel: evaluate(attribute(toggle, ':aria-label')),
        }),
    };
}

for (const groupId of [...groupIds, null]) {
    test(`accordion initializes from ${groupId ?? 'no'} server-expanded group`, () => {
        const sidebar = createSidebar(1440, 'stale-group');

        sidebar.initializeGroup(groupId);

        assert.equal(sidebar.state.openGroup, groupId);

        for (const id of groupIds) {
            const isOpen = id === groupId;

            assert.deepEqual(sidebar.group(id), { expanded: isOpen, open: isOpen, hidden: !isOpen, inert: !isOpen });
        }
    });
}

for (const groupId of groupIds) {
    for (const width of [375, 1440]) {
        test(`${groupId} opens and closes its own accordion panel at ${width}px`, () => {
            const sidebar = createSidebar(width);

            sidebar.clickGroup(groupId);

            assert.equal(sidebar.state.openGroup, groupId);

            for (const id of groupIds) {
                const isOpen = id === groupId;

                assert.deepEqual(sidebar.group(id), { expanded: isOpen, open: isOpen, hidden: !isOpen, inert: !isOpen });
            }

            sidebar.clickGroup(groupId);

            assert.equal(sidebar.state.openGroup, null);

            for (const id of groupIds) {
                assert.deepEqual(sidebar.group(id), { expanded: false, open: false, hidden: true, inert: true });
            }
        });
    }
}

for (const width of [320, 375, 768, 1024, 1279, 1280, 1440]) {
    test(`navigation opens and closes correctly at ${width}px`, () => {
        const sidebar = createSidebar(width);
        const isDesktop = width >= 1280;

        assert.equal(sidebar.state.isDesktop, isDesktop);
        assert.equal(sidebar.state.sidebarToggle, false);
        assert.equal(sidebar.state.desktopSidebarCollapsed, false);
        assert.deepEqual(sidebar.accessibility(), {
            sidebarHidden: !isDesktop,
            sidebarInert: !isDesktop,
            contentInert: false,
            backdropVisible: false,
            toggleExpanded: isDesktop ? 'true' : 'false',
            toggleLabel: isDesktop ? 'Collapse navigation' : 'Open navigation',
        });

        sidebar.toggle();
        sidebar.flushTicks();

        assert.equal(sidebar.state.sidebarToggle, !isDesktop);
        assert.equal(sidebar.state.desktopSidebarCollapsed, isDesktop);
        assert.deepEqual(sidebar.accessibility(), {
            sidebarHidden: false,
            sidebarInert: false,
            contentInert: !isDesktop,
            backdropVisible: !isDesktop,
            toggleExpanded: isDesktop ? 'false' : 'true',
            toggleLabel: isDesktop ? 'Expand navigation' : 'Close navigation',
        });
        assert.equal(sidebar.focus.sidebarItem, isDesktop ? 0 : 1);

        sidebar.toggle();
        sidebar.flushTicks();

        assert.equal(sidebar.state.sidebarToggle, false);
        assert.equal(sidebar.state.desktopSidebarCollapsed, false);
        assert.equal(sidebar.focus.trigger, isDesktop ? 0 : 1);
        assert.equal(sidebar.accessibility().contentInert, false);
    });
}

test('resizing an open mobile drawer to desktop closes the drawer and clears its focus trigger', () => {
    const sidebar = createSidebar(375);

    sidebar.toggle();
    sidebar.flushTicks();
    sidebar.resize(1440);

    assert.equal(sidebar.state.isDesktop, true);
    assert.equal(sidebar.state.sidebarToggle, false);
    assert.equal(sidebar.state.sidebarTrigger, null);
    assert.equal(sidebar.accessibility().backdropVisible, false);
    assert.equal(sidebar.accessibility().contentInert, false);

    sidebar.resize(375);

    assert.equal(sidebar.state.isDesktop, false);
    assert.equal(sidebar.state.sidebarToggle, false);
    assert.equal(sidebar.accessibility().sidebarInert, true);
});

test('desktop collapse preference survives mobile opening and viewport changes independently', () => {
    const sidebar = createSidebar(1440);

    sidebar.toggle();
    sidebar.resize(375);

    assert.equal(sidebar.state.desktopSidebarCollapsed, true);
    assert.equal(sidebar.state.sidebarToggle, false);
    assert.equal(sidebar.accessibility().toggleLabel, 'Open navigation');

    sidebar.toggle();
    sidebar.flushTicks();

    assert.equal(sidebar.state.sidebarToggle, true);
    assert.equal(sidebar.state.desktopSidebarCollapsed, true);
    assert.equal(sidebar.accessibility().sidebarInert, false);

    sidebar.resize(1440);

    assert.equal(sidebar.state.sidebarToggle, false);
    assert.equal(sidebar.state.desktopSidebarCollapsed, true);
    assert.equal(sidebar.accessibility().toggleExpanded, 'false');
    assert.equal(sidebar.accessibility().contentInert, false);
});

test('resizing within mobile widths keeps the drawer open and Escape restores focus', () => {
    const sidebar = createSidebar(375);

    sidebar.toggle();
    sidebar.flushTicks();
    sidebar.resize(768);
    sidebar.resize(1279);

    assert.equal(sidebar.state.sidebarToggle, true);
    assert.equal(sidebar.accessibility().backdropVisible, true);

    sidebar.escape();
    sidebar.flushTicks();

    assert.equal(sidebar.state.sidebarToggle, false);
    assert.equal(sidebar.focus.trigger, 1);
    assert.equal(sidebar.accessibility().sidebarInert, true);
    assert.equal(sidebar.accessibility().contentInert, false);
});

for (const action of ['close', 'backdropClick']) {
    test(`${action} closes the mobile drawer and returns focus to its trigger`, () => {
        const sidebar = createSidebar(320);

        sidebar.toggle();

        assert.equal(sidebar.focus.sidebarItem, 0);

        sidebar.flushTicks();

        assert.equal(sidebar.focus.sidebarItem, 1);
        assert.equal(sidebar.focus.trigger, 0);

        sidebar[action]();
        sidebar.flushTicks();

        assert.equal(sidebar.state.sidebarToggle, false);
        assert.equal(sidebar.focus.trigger, 1);
        assert.equal(sidebar.accessibility().backdropVisible, false);
    });
}

test('opening another accordion group closes the previous group and disables its children', () => {
    const sidebar = createSidebar(1440);

    sidebar.clickGroup('user-management');

    assert.deepEqual(sidebar.group('user-management'), { expanded: true, open: true, hidden: false, inert: false });
    assert.deepEqual(sidebar.group('job-management'), { expanded: false, open: false, hidden: true, inert: true });

    sidebar.clickGroup('job-management');

    assert.equal(sidebar.state.openGroup, 'job-management');
    assert.deepEqual(sidebar.group('user-management'), { expanded: false, open: false, hidden: true, inert: true });
    assert.deepEqual(sidebar.group('job-management'), { expanded: true, open: true, hidden: false, inert: false });

    sidebar.clickGroup('job-management');

    assert.equal(sidebar.state.openGroup, null);
    assert.deepEqual(sidebar.group('job-management'), { expanded: false, open: false, hidden: true, inert: true });
});

test('clicking the active group while desktop navigation is collapsed expands its submenu', () => {
    const sidebar = createSidebar(1440, 'job-management');

    sidebar.toggle();

    assert.equal(sidebar.state.desktopSidebarCollapsed, true);

    sidebar.clickGroup('job-management');

    assert.equal(sidebar.state.desktopSidebarCollapsed, false);
    assert.equal(sidebar.state.sidebarToggle, false);
    assert.equal(sidebar.state.openGroup, 'job-management');
    assert.deepEqual(sidebar.group('job-management'), { expanded: true, open: true, hidden: false, inert: false });

    sidebar.clickGroup('job-management');

    assert.equal(sidebar.state.openGroup, null);
});

test('mobile accordion clicks preserve the desktop collapse preference and toggle the active group', () => {
    const sidebar = createSidebar(1440, 'job-management');

    sidebar.toggle();
    sidebar.resize(375);
    sidebar.toggle();
    sidebar.flushTicks();
    sidebar.clickGroup('job-management');

    assert.equal(sidebar.state.isDesktop, false);
    assert.equal(sidebar.state.desktopSidebarCollapsed, true);
    assert.equal(sidebar.state.sidebarToggle, true);
    assert.equal(sidebar.state.openGroup, null);
    assert.deepEqual(sidebar.group('job-management'), { expanded: false, open: false, hidden: true, inert: true });

    sidebar.clickGroup('job-management');

    assert.equal(sidebar.state.desktopSidebarCollapsed, true);
    assert.deepEqual(sidebar.group('job-management'), { expanded: true, open: true, hidden: false, inert: false });

    sidebar.resize(1440);

    assert.equal(sidebar.state.desktopSidebarCollapsed, true);
    assert.equal(sidebar.state.sidebarToggle, false);
});
