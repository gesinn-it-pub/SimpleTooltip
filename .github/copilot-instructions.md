<!-- THIS FILE IS AUTO-GENERATED. Edit .github/copilot-instructions-source.adoc instead. -->

# Coding Conventions

**Coding Conventions — General**

All source files regardless of language must follow these baseline
rules. They are enforced by `make ci` (lint + phpcs + eslint).

- Encoding: UTF-8 without BOM

- Line endings: Unix-style LF (not CR+LF)

- Indentation: tabs, not spaces

- Maximum line length: 120 characters

- No trailing whitespace

- Newline at end of file

**Coding Conventions — PHP**

Tooling:
[mediawiki-codesniffer](https://github.com/wikimedia/mediawiki-tools-codesniffer)
via PHPCS. Run locally: `make composer-phpcs` (or `make ci`).

**File structure**

- Every file starts with `declare( strict_types=1 );`

- No closing `?>` tag

- One class per file; filename matches class name (UpperCamelCase, e.g.
  `MyClass.php`)

- New code belongs in `src/` following PSR-4; `includes/` is legacy and
  should be migrated incrementally

**Namespaces and autoloading**

- PSR-4 via Composer (`autoload.psr-4` in `composer.json`)

- Top-level namespace = extension name (e.g.
  `MediaWiki\Extension\FooBar...`)

- Acronyms treated as single words: `HtmlId`, not `HTMLId`

**Naming**

| Element                     | Convention     | Example                |
|-----------------------------|----------------|------------------------|
| Classes, interfaces, traits | UpperCamelCase | `PageFormParser`       |
| Methods, variables          | lowerCamelCase | `getFormContent()`     |
| Constants                   | UPPER_CASE     | `MAX_FORM_SIZE`        |
| Global variables            | `$wg` prefix   | `$wgPageFormsSettings` |

**Type system**

- Use native type declarations on all parameters, properties, and return
  types

- PHPDoc only when native types are insufficient (e.g. `string[]`,
  `array<string, Foo>`)

- Nullable parameters: `?Type`, not `Type $x = null`

- Prefer `??` (null coalescing) and `??=` over ternary isset checks

- Use arrow functions `fn( $x ) ⇒ $x * 2` for single-expression closures

**Modern PHP features (target: PHP 8.1+)**

- Constructor property promotion

- `readonly` properties for immutable value objects

- `enum` instead of class constant groups

- `match()` instead of `switch` when returning a value

**Code style**

- 1TBS brace style — opening brace on same line, `else`/`elseif` on
  closing brace line

- Always use braces, even for single-line blocks

- Spaces inside parentheses: `getFoo( $bar )`, empty: `getBar()`

- Spaces around binary operators: `$a = $b + $c`

- Single quotes preferred; double quotes for string interpolation

- `===` strict equality; `==` only when type coercion is intentional

- No Yoda conditions: `$a === 'foo'`, not `'foo' === $a`

- `elseif` not `else if`

- `true`, `false`, `null` always lowercase

**Architecture**

- `private` by default; `protected` only when subclass access is needed

- Dependency injection over direct instantiation — delegate `new Foo()`
  to factories

- Single Responsibility: one class, one concern

- No superglobals (`$_GET`, `$_POST`) — use `WebRequest` via
  `RequestContext`

- No new global functions — use static utility classes (`Html`, `IP`) if
  needed

- Order class members: `public` → `protected` → `private`

**Static Analysis — Phan**

Tooling: [Phan](https://github.com/phan/phan) with
[mediawiki-phan-config](https://github.com/wikimedia/mediawiki-phan-config).
Run locally: `make composer-phan` (or `make dev-test`).

**Setup**

Add the Phan script to `composer.json`:

``` json
"scripts": {
    "phan": "phan --allow-polyfill-parser"
}
```

<div class="note">

`--allow-polyfill-parser` activates a pure-PHP AST fallback. Required
when the native `php-ast` extension is not available (e.g. Debian trixie
/ PHP 8.3 where `php-ast` has no apt package). Without this flag Phan
exits immediately if `php-ast` is absent.

</div>

Add the `composer-phan` target to the extension `Makefile`:

``` makefile
composer-phan: ## Run Phan static analysis
    $(DOCKER_EXEC) composer phan
```

**Configuration**

`.phan/config.php` inherits from `mediawiki-phan-config`:

``` php
$cfg = require __DIR__ . '/../vendor/mediawiki/mediawiki-phan-config/src/config.php';

$cfg['baseline_path'] = __DIR__ . '/baseline.php';

$cfg['directory_list'] = array_merge(
    $cfg['directory_list'],
    ['src', 'includes', 'specials']
);

$cfg['exclude_analysis_directory_list'] = array_merge(
    $cfg['exclude_analysis_directory_list'],
    ['vendor/']
);

return $cfg;
```

**Baseline**

- `.phan/baseline.php` is auto-generated — do not edit it manually

- New code must not introduce Phan issues beyond the current baseline

- When deliberately deferring a pre-existing issue, update the
  baseline:  
  `composer phan — --save-baseline=.phan/baseline.php`

- When suppressing with `@suppress`, always add an explanatory comment

**Coding Conventions — JavaScript**

Tooling: [ESLint](https://eslint.org/) with
[eslint-config-wikimedia](https://github.com/wikimedia/eslint-config-wikimedia).
Run locally: `npm run lint:js` (or `make ci`).

**ESLint configuration**

Every repository must have a `.eslintrc.json` at root with
`"root": true`:

``` json
{
  "root": true,
  "extends": [
    "wikimedia/client/es2016",
    "wikimedia/jquery",
    "wikimedia/mediawiki"
  ],
  "env": { "commonjs": true }
}
```

**Module system**

- CommonJS modules: `require()` for imports, `module.exports` for
  exports

- Register modules with ResourceLoader; bundle name pattern:
  `ext.myExtension`

- JS class files match the class name exactly (`TitleWidget.js` for
  `TitleWidget`)

**Naming**

- Variables and methods: lowerCamelCase

- Constructors / classes: UpperCamelCase

- jQuery objects: `$`-prefix (`$button`, not `button`)

- Constants: `ALL_CAPS`

- Acronyms as single words: `getHtmlApiSource`, not `getHTMLAPISource`

**Code style**

- Tabs for indentation; single quotes for string literals

- `===` and `!==`; no Yoda conditions

- Spaces inside parentheses: `if ( foo )`, `getFoo( bar )`

- `const` and `let` — never `var` in new code

- Arrow functions for callbacks

**jQuery**

- Prefer ES6/DOM equivalents over deprecated jQuery methods (`.each` →
  `forEach`, etc.)

- Never search the full DOM with `$( '#id' )` or `$( '.selector' )`; use
  hook-provided `$content` and call `.find()` on it *(full-DOM queries
  match stale or foreign nodes, break hook-lifecycle isolation, and
  waste performance by traversing the entire document)*

- Prefer `$( '<div>' ).text( value )` over `$( '<div>text</div>' )` to
  avoid XSS

**MediaWiki APIs**

- Access configuration via `mw.config.get( 'wgFoo' )`, never direct
  globals

- Expose public API via `module.exports` or within the `mw` namespace
  (e.g. `mw.echo.Foo`)

- Use `mw.storage` / `mw.storage.session` for
  localStorage/sessionStorage

- Storage keys: `mw`-prefix + camelCase/hyphens (e.g.
  `mwedit-state-foo`)

**Coding Conventions — CSS / LESS**

Tooling: [stylelint](https://stylelint.io/) via `npm run lint:styles`
(or `make ci`). ResourceLoader natively compiles `.less` files; prefer
LESS over plain CSS.

**Naming**

- Classes and IDs: all-lowercase, hyphen-separated

- Use an extension-specific prefix to avoid conflicts (e.g. `pf-`,
  `smw-`, `mw-`)

- LESS mixin names: `mixin-` prefix + hyphen-case (e.g.
  `mixin-screen-reader-text`)

**Whitespace and formatting**

- One selector per line, one property per line

- Opening brace on the same line as the last selector

- Tab indentation for properties and nested rules

- Semicolon after every declaration, including the last

- Empty line between rule sets

**Colors**

- Lowercase hex shorthand preferred: `#fff`, `#252525`

- `rgba()` when alpha transparency is needed; `transparent` keyword
  otherwise

- No named color keywords (except `transparent`), no `rgb()`, `hsl()`,
  `hsla()`

- Ensure color contrast meets [WCAG 2.0
  AA](https://www.w3.org/TR/WCAG20/)

**LESS specifics**

- CSS custom properties (design tokens) preferred over LESS variables
  for new code

- `@import` only for mixins and variables (`variables.less`,
  `mixins.less`); do not use `@import` for bundling conceptually related
  files

- Omit `.less` extension in `@import` statements

- Bundle related files via the `styles` array in `skin.json` /
  `extension.json`

**Anti-patterns to avoid**

- `!important` — avoid except when overriding upstream code that also
  uses it

- `z-index` — use natural DOM stacking order where possible; document
  exceptions

- Inline `style` attributes — always use stylesheet classes instead

- `float` / `text-align: left` hardcoded — use `/* @noflip */`
  annotation when needed, otherwise ResourceLoader’s CSSJanus handles
  RTL automatically

# Test Workflow

**Test-first approach**

Before making any code changes to fix a bug or implement a feature:

1.  Check whether an existing test already covers the described
    behavior.

2.  If not, write or adapt a test that reproduces the issue — it must
    fail first.

3.  Only after a failing test exists, make the code changes.

4.  Re-run the test to confirm it passes (green).

**Test environment setup**

All tests run inside a containerized MediaWiki environment managed via
[docker-compose-ci](https://github.com/gesinn-it-pub/docker-compose-ci)
(the `build/` submodule). Never run tests directly against a local PHP
or Node.js installation.

Always run `make install` before executing tests to ensure that the
latest file changes are copied into the container. Changes to source or
test files on the host are **not** automatically reflected in a running
container.

``` console
make install
```

**PHPUnit tests**

Run all PHPUnit tests:

``` console
make install composer-phpunit
```

Run a single test class or method (filtered):

``` console
make install composer-phpunit COMPOSER_PARAMS="-- --filter YourTestName"
```

Run a specific test suite:

``` console
make install composer-phpunit COMPOSER_PARAMS="-- --testsuite your-suite-name"
```

For interactive use, bash into the running container:

``` console
make bash
> composer phpunit -- --filter YourTestName
```

**Phan — static analysis**

Run Phan against the codebase:

``` console
make composer-phan
```

**Fixing issues**

- Fix genuine type errors, undeclared-method, and undeclared-class
  issues in new code

- For issues in legacy code not touched by the current change, update
  the baseline instead of adding `@suppress`:

  ``` console
  composer phan -- --save-baseline=.phan/baseline.php
  ```

- When `@suppress` is unavoidable, add an explanatory comment directly
  above it

**Baseline updates**

`.phan/baseline.php` is auto-generated. After updating it, commit it
together with the code change that necessitated the update.

**Node QUnit tests**

Run all JavaScript tests:

``` console
make install npm-test
```

There is no direct `make` target for filtering individual tests. Bash
into the running container to run a specific test file or test case:

``` console
make bash
> npm run node-qunit -- tests/node-qunit/yourtest.test.js
```

Filter by test description:

``` console
make bash
> npx qunit --require ./tests/node-qunit/setup.js 'tests/node-qunit/**/*.test.js' --filter "your test description"
```

**Pre-commit validation gate**

Before every commit, run the full CI suite to confirm nothing is broken:

``` console
make ci
```

For interactive use (volume-mounted extension, no container rebuild),
use the faster pre-commit gate:

``` console
make dev-test
```

`dev-test` runs: lint → PHPCS → Phan → PHPUnit — without destroying
Docker volumes. Reserve `make ci` for the full pipeline verification.

# Commit Convention

# Conventional Commits Policy

Commit messages follow the [Conventional Commits
specification](https://www.conventionalcommits.org/).

Commit format:

`type(scope): short description`

The scope is optional and should describe the affected subsystem,
module, or dependency when useful.

Examples:

- feat(api): add autocomplete endpoint

- fix(parser): handle empty token lists

- docs(readme): explain input architecture

- refactor(parser): simplify token parsing

- deps(smw): bump from 5.1.0 to 5.2.0

- ci(github): update workflow configuration

- test(api): add autocomplete tests

Recommended commit types:

- `feat` — new functionality

- `fix` — bug fixes

- `deps` — dependency updates

- `docs` — documentation changes

- `refactor` — internal code changes without behavioral change

- `test` — tests added or updated

- `ci` — changes to continuous integration configuration

- `chore` — repository maintenance tasks without impact on runtime
  behavior

Dependency updates:

- Use the `deps` type for dependency upgrades

- The scope should identify the dependency being updated

- Include the version change when applicable

Example:

- deps(smw): bump from 5.1.0 to 5.2.0

Guidelines:

- Use the imperative mood (e.g. "add feature", not "added feature")

- Keep the subject line concise

- Use the commit body to explain **why**, not only **what**

- Scopes should be short, lowercase identifiers (e.g. `api`, `parser`,
  `smw`, `mediawiki`, `docker`)

- Use `chore` only for repository maintenance tasks that do not affect
  runtime behavior, dependencies, CI configuration, or tests

# Versioning

# Versioning and Releases

This project follows [Semantic Versioning](https://semver.org/).

Version numbers follow the format:

`MAJOR.MINOR.PATCH`

Version increment rules:

- MAJOR — incompatible or breaking changes

- MINOR — backwards-compatible feature additions

- PATCH — backwards-compatible bug fixes

Breaking changes include (but are not limited to):

- incompatible API changes

- removal or renaming of public interfaces

- behavior changes that may break existing integrations

- increased minimum runtime or dependency requirements

- incompatible configuration or data format changes

- dependency upgrades that introduce breaking changes for users

Breaking changes must always increment the MAJOR version.
