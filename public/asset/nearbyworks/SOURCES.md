# NearbyWorks frontend asset sources

Retrieved 23 October 2026. All shipped images are stored locally in this folder; pages do not hotlink these files.

## Workplace photography

Discovered through the requested [Made in Webflow assets showcase](https://webflow.com/made-in-webflow/assets).

Source project: [Tax Software Webflow Landing by Tinyflow / Finestdev](https://webflow.com/made-in-webflow/website/webflow-tax-software-landing).
The showcase description identifies this as a free landing page shared with the community and provides a Clone in Webflow action. [Live source](https://webflow-tax-software-landing.webflow.io/).

| Local file | Description | Exact upstream file |
| --- | --- | --- |
| `professional.png` | Two colleagues working together in a warm shared workspace; 495 by 621 pixels | https://assets.website-files.com/62a9b8a26abff8c87de8071e/62a9ceab0722ee715db1e204_hero-1.png |
| `conversation.png` | Professional greeting someone on a laptop video call; 585 by 444 pixels | https://assets.website-files.com/62a9b8a26abff8c87de8071e/62aa3e72df25a41b603091f1_author.png |

Use these as illustrative workplace photography, not as named NearbyWorks employees, customers, testimonials, or evidence of real platform activity. Files are the original compact source PNGs, without generated imagery.

## Independent career-resource logos

Requested discovery source: [Logos by lndev](https://logos.lndev.me/).
Source repository: [ln-dev7/logos-apps](https://github.com/ln-dev7/logos-apps).

| Local file | Brand | Exact upstream file | Outbound resource destination |
| --- | --- | --- | --- |
| `linkedin.svg` | LinkedIn | https://raw.githubusercontent.com/ln-dev7/logos-apps/master/logos/linkedin.svg | https://www.linkedin.com/ |
| `indeed.svg` | Indeed | https://raw.githubusercontent.com/ln-dev7/logos-apps/master/logos/indeed.svg | https://www.indeed.com/ |
| `glassdoor.svg` | Glassdoor | https://raw.githubusercontent.com/ln-dev7/logos-apps/master/logos/glassdoor.svg | https://www.glassdoor.com/ |

The repository's code and organization use the MIT license, but the logos themselves remain trademarks and property of their respective owners. The repository explicitly says its inclusion does not grant usage rights or imply affiliation or endorsement; brand guidelines still apply.

These logos identify outbound independent career resources only. They must not be presented as NearbyWorks' identity, customers, partners, integrations, or endorsers. The NearbyWorks wordmark and any original geometric brand treatment are separate from these third-party marks.

## Hero map

Retrieved 24 October 2026.

- **Land outlines:** [Natural Earth](https://www.naturalearthdata.com/) 110m land, public domain. Converted to a dot grid by `resources/build-world-dots.mjs` (output in `resources/js/world-land.js`). Source file: https://raw.githubusercontent.com/nvkelso/natural-earth-vector/master/geojson/ne_110m_land.geojson
- **Country flags:** [`flags/`](flags/) holds circular country flags from [HatScripts/circle-flags](https://github.com/HatScripts/circle-flags), MIT licence, Copyright (c) 2026 HatScripts. Each file is the unmodified `flags/{code}.svg` from that repository.

The map's design was inspired by the dotted-globe hero on pxxl.app. No code or artwork from that site is used.
