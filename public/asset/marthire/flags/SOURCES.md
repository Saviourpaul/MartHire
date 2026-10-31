# Local country flag assets

Completed 30 October 2026 against all **250** country codes in the installed
`nnjeim/world` package's `resources/json/countries.json`, including `XK` (Kosovo).
Every supported country has a lowercase `{iso2}.svg` file in this directory.

The circular SVG artwork comes from the official
[HatScripts/circle-flags repository](https://github.com/HatScripts/circle-flags),
whose default branch is `gh-pages`.

- Added **191** missing files from revision
  [`379588b5da95482d6bbf10bd45644a35b0609ea6`](https://github.com/HatScripts/circle-flags/tree/379588b5da95482d6bbf10bd45644a35b0609ea6/flags).
- Downloaded the [official source archive](https://codeload.github.com/HatScripts/circle-flags/zip/379588b5da95482d6bbf10bd45644a35b0609ea6).
- Preserved the original **59** SVG files retrieved on 24 October 2026, unchanged.
- Each added asset is an unmodified upstream SVG. Six upstream country files
  are Git symbolic links; their target SVG bytes are copied into the local
  `{iso2}.svg` files so they work on Windows and ordinary static hosts.
  These are the source project's own aliases, rather than guessed replacements.
- Preserved [LICENSE.md](LICENSE.md): MIT License, Copyright (c) 2026 HatScripts.

[coverage.json](coverage.json) records the country codes, pinned source revision,
and validation results. The original World coverage is **250 countries**; the
phone-country picker adds **AC** (Ascension Island) and **TA** (Tristan da Cunha),
bringing the local set to **252 SVG files**. Both additional flags use the same
pinned official revision, with upstream aliases resolved to SVG bytes.
All files were parsed as SVG XML with external entity resolution disabled;
no scripts, foreign objects, event attributes, or external resource references
were found. Added files match upstream SHA-256 hashes, and the original 59 files
retain their previous hashes.

The source archive identifies all six entries below as symbolic links (Unix
mode `120777`). The matching targets are included in the same pinned archive.

| Local file | Upstream symbolic-link target |
| --- | --- |
| `bq.svg` | `bq-bo.svg` |
| `bv.svg` | `no.svg` |
| `hm.svg` | `au.svg` |
| `sh.svg` | `sh-hl.svg` |
| `sj.svg` | `no.svg` |
| `um.svg` | `us.svg` |

Application pages serve these assets locally; they do not fetch flag images from
GitHub or a third-party image host at runtime.
