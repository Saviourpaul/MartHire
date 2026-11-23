# Documentation lookup

For work that depends on current or version-specific third-party library APIs, use the Context7 MCP tools: resolve the library ID first, then query its documentation. Prefer the checked-in application code for project-specific behavior, and skip Context7 for general questions or stable conventions that do not depend on library documentation.

## Code review

When the user requests a code review in this repository, follow the project skill at `.agents/skills/code-review/SKILL.md`.

## Browser verification

When making code changes, use the installed Playwright setup with Chrome to verify the affected application flow whenever it can be exercised in a browser. Do not rely only on static checks when browser verification is practical. If Chrome or the app environment is unavailable, report that limitation and run the closest relevant checks.

## Installed agent skills

At the start of each coding task, check `.codeartsdoer/skills` for a skill matching the work and read its `SKILL.md`. Apply its workflow when relevant; the linked skill files are the reference source.
