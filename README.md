# Interactivity Docs

A WordPress plugin that powers a reactive, filterable documentation archive
using the native **WordPress Interactivity API** — no React, no extra framework
on the front end.

[![WordPress](https://img.shields.io/badge/WordPress-%E2%89%A56.5-blue?logo=wordpress)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-%E2%89%A58.0-777bb4?logo=php)](https://php.net)
[![License](https://img.shields.io/badge/license-GPL--2.0--or--later-green)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## Highlights

- **Reactive UI without a framework** — built on the WordPress Interactivity API
  (store, context, actions, callbacks). Zero front-end framework dependency.
- **Strategy-based filter pipeline** — `clientStrategy`, `serverStrategy`,
  `singlePageStrategy`, and `sortStrategy` swap at runtime; the view layer stays
  free of conditional branching.
- **Composable state layer** — state is split into focused getter modules
  (`composeState`, `layoutState`, `menuState`, `selectorState`, `sortState`,
  `uiState`), each with a single responsibility.
- **Repository + Sync architecture** — typed repositories
  (`BookRepository`, `PaperRepository`, `PersonRepository`,
  `RelationRepository`) behind interfaces, with a `SyncCoordinator` keeping
  post data and relations in sync.
- **Custom REST layer** — dedicated controller, routes, and config
  (`DocsController`, `DocsRoutes`, `ApiConfig`, `SortConfig`) power
  server-side filtering, sorting, and pagination.
- **ACF as code** — all field groups live in `acf-json/` and are
  version-controlled. Schema is in the repo, not the database.
- **Zero runtime Composer dependency** — a fallback PSR-4 autoloader runs when
  `vendor/` is absent, so the distributed ZIP installs without Composer.

---

## Requirements

| Requirement | Version |
|---|---|
| WordPress | ≥ 6.5 |
| PHP | ≥ 8.0 |
| Advanced Custom Fields (ACF) | ≥ 6.0 (free or Pro) |

> WordPress 6.5+ handles the ACF dependency automatically via the
> `Requires Plugins: advanced-custom-fields` plugin header.

---

## Installation

### End-user (ZIP)

1. Download the latest `interactivity-docs.zip` from
   [Releases](https://github.com/hadimahoor/interactivity-docs/releases).
2. In WordPress admin go to **Plugins → Add New → Upload Plugin**.
3. Upload the ZIP and click **Activate**.
4. Make sure **Advanced Custom Fields** is installed and active.

> Composer is not required. The ZIP ships with pre-built assets and a
> bundled fallback autoloader.

### Developer (Clone)
```bash
git clone https://github.com/hadimahoor/interactivity-docs.git \
  wp-content/plugins/interactivity-docs

cd wp-content/plugins/interactivity-docs

composer install   # dev tools only (phpcs) — not needed at runtime
npm install
npm run build

---

## Development

| Command | Description |
|---|---|
| `npm run build` | Production build (minified) |
| `npm run start` | Development watch mode |
| `npm run lint:js` | ESLint via `@wordpress/scripts` |
| `npm run format` | Prettier formatting |
| `composer lint` | PHP_CodeSniffer — PSR-12, line limit 180 |

---

## Architecture


interactivity-docs/
├── interactivity-docs.php          # Bootstrap: headers, constants, hybrid autoloader
├── composer.json / composer.lock
├── package.json / package-lock.json
├── phpcs.xml.dist                  # PSR-12, line limit 180
├── README.md / readme.txt
├── acf-json/                       # ACF Local JSON (version-controlled field groups)
├── build/                          # Compiled assets (JS/CSS, shipped in ZIP)
├── data/
├── languages/                      # i18n .pot/.po/.mo files
└── src/
├── Plugin.php                  # Orchestrator — boots all services (DI)
│
├── BlockManager/
│   └── BlockRegistrar.php
│
├── Database/
│   ├── SchemaManager.php
│   └── TableNames.php
│
├── Integration/
│   └── AcfManager.php           # ACF paths + dependency notice
│
├── Models/
│   ├── BaseEntity.php
│   ├── Book.php
│   ├── Paper.php
│   └── Person.php
│
├── PostTypes/
│   └── PostTypeRegistrar.php
│
├── Repository/
│   ├── BasePostRepository.php
│   ├── BaseRepository.php
│   ├── BookRepository.php
│   ├── PaperRepository.php
│   ├── PersonRepository.php
│   ├── PostRepositoryInterface.php
│   ├── RelationRepository.php
│   ├── RelationRepositoryInterface.php
│   ├── RepositoryFactory.php
│   └── RepositoryInterface.php
│
├── Rest/
│   ├── Config/
│   │   ├── ApiConfig.php
│   │   └── SortConfig.php
│   ├── Controllers/
│   │   └── DocsController.php
│   └── Routes/
│       ├── DocsRoutes.php
│       └── RouteRegistrar.php
│
├── Support/
│   ├── TemplateLoader.php
│   └── UIHelpers.php
│
├── Sync/
│   ├── PostSyncManager.php
│   ├── RelationSyncService.php
│   └── SyncCoordinator.php
│
├── Taxonomies/
│   └── TaxonomyRegistrar.php
│
└── blocks/
└── interactive/
├── non-interactive/
└── docs-archive/
├── block.json
├── index.js            # Block editor entrypoint
├── edit.js             # Block editor UI
├── view.js             # Front-end Interactivity API entrypoint
├── render.php          # Server-side render
├── style.scss / editor.scss
├── README.md
│
├── actions/            # Store actions
│   ├── core.js
│   ├── menuActions.js
│   ├── paginationAction.js
│   ├── removeFilterAction.js
│   ├── selectFilterAction.js
│   └── termAction.js
│
├── callbacks/          # Interactivity API lifecycle callbacks
│   ├── lifecycleCallbacks.js
│   └── menuCallbacks.js
│
├── pagination/
│   └── pagination.js
│
├── pipeline/           # Filter execution pipeline
│   ├── pipeline.js
│   ├── clientPipeline.js
│   └── serverPipeline.js
│
├── request/
│   └── request.js      # REST request layer
│
├── state/              # Reactive state modules
│   ├── context.js
│   ├── loading.js
│   ├── menu.js
│   ├── singlePage.js
│   └── getters/
│       ├── composeState.js
│       ├── layoutState.js
│       ├── menuState.js
│       ├── selectorState.js
│       ├── sortState.js
│       └── uiState.js
│
├── strategies/         # Runtime filter strategies (Strategy Pattern)
│   ├── strategies.js
│   ├── clientStrategy.js
│   ├── serverStrategy.js
│   ├── singlePageStrategy.js
│   └── sortStrategy.js
│
├── cache/
│   └── cache.js
│
├── style/              # SCSS partials
│   ├── _baseline.scss
│   ├── _card-default.scss
│   ├── _card-person.scss
│   ├── _docs-list.scss
│   ├── _filters.scss
│   └── _pagination.scss
│
├── templates/          # PHP render templates
│   ├── cards/
│   │   ├── card-book.php
│   │   ├── card-default.php
│   │   ├── card-person.php
│   │   └── card-post.php
│   ├── components/
│   │   ├── book-cover.php
│   │   ├── like-button.php
│   │   ├── person-actions.php
│   │   ├── person-counters.php
│   │   ├── post-actions.php
│   │   ├── save-button.php
│   │   └── taxonomies.php
│   └── layout/
│       ├── docs-list.php
│       ├── filters.php
│       └── pagination.php
│
├── dropdown-menu-block/         # TS-based menu blocks
│   ├── block.json
│   ├── edit.tsx / save.tsx / index.tsx
│   ├── view.ts
│   └── style.scss / editor.scss
├── dropdown-menu-content-block/
├── dropdown-menu-item-block/
└── dropdown-menu-trigger-block/

---

## Key Design Decisions

- **Zero runtime Composer dependency** — a fallback PSR-4 autoloader in the
  bootstrap handles class loading when `vendor/` is absent (production ZIP).
- **Strategy pattern** — the filter strategy is selected once at init; the
  pipeline calls a unified interface regardless of execution context
  (client, server, single-page, sort).
- **Repository + Factory** — `RepositoryFactory` resolves typed repositories
  behind shared interfaces, isolating data access from REST controllers.
- **Composable state** — each state getter module owns one concern and is
  combined in `composeState.js`, keeping `view.js` declarative.
- **ACF Local JSON** — field group schema lives in `acf-json/` and travels with
  the repo. No manual DB export/import across environments.

---

## License

[GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)

---

*Built by [Hadi Khodayari](https://github.com/hadimahoor)*


