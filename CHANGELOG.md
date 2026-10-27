# Changelog

All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](https://semver.org/) and
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

## [3.0.0] - 2026-10-27

Tooltips now accept wikitext everywhere, the output of tooltip text is more robust, and the extension only loads its module on pages that use a tooltip. Several changes affect existing output, see Breaking Changes.

### Breaking Changes
- `#tip-img` takes the name of a file on the wiki (with or without `File:`) instead of a path or URL. External images are accepted only as http(s) URLs and only if `$wgAllowExternalImages` or `$wgAllowExternalImagesFrom` allows them, otherwise an error message is shown [`7792c77`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/7792c77)
- The tooltip content is a `<div class="simple-tooltip__content">` without an outer paragraph, and the hovered text of `#tip-text` is parsed as wikitext instead of being shown as plain text. Custom CSS or scripts that rely on the old markup may need an update [`97dbd31`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/97dbd31), [`7792c77`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/7792c77)
- The module `ext.SimpleTooltip` is only loaded on pages that contain a tooltip, no longer on every page. Scripts that use `mw.libs.SimpleTooltip` on other pages must load the module themselves. With PageForms up to 2.4.0, tooltips in a form are not initialised after a preview, a diff or a failed save; this is fixed in PageForms after 2.4.0 [`944f97d`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/944f97d)

### Added
- Wikitext (formatting, links, templates) works in the tooltip text of `#tip-text`, `#tip-info` and `#tip-img` and in the hovered text of `#tip-text` [`7792c77`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/7792c77) ([#7](https://github.com/gesinn-it-pub/SimpleTooltip/issues/7))

### Changed
- Quotation marks in tooltip text are no longer replaced by apostrophes [`7792c77`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/7792c77)
- Remove the unused setting `SimpleTooltipSubmitText` [`4cb1fe6`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/4cb1fe6)
- Document the bundled Tooltipster 3.3.0 and link to its new home [`b57dbd0`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/b57dbd0), [`633bfb3`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/633bfb3) ([#15](https://github.com/gesinn-it-pub/SimpleTooltip/pull/15))
- Update the development tools; running the checks needs PHP 8.2 or newer [`898489d`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/898489d)

### Fixed
- Make the output of tooltip text more robust: text that was already escaped is no longer converted to markup in the browser, and tooltip content is stripped of scripts and event handlers. Updating from 2.0.0 and 2.0.1 is recommended [`17e5229`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/17e5229)
- Show tooltips in content that is added later, such as a live preview, and do not initialise a tooltip twice [`d925a9a`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/d925a9a)
- Fix a PHP warning when `#tip-text` or `#tip-img` has no tooltip text, and show the tooltip text `0` [`5bf9277`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/5bf9277)
- Work on MediaWiki 1.46 [`173ec4d`](https://github.com/gesinn-it-pub/SimpleTooltip/commit/173ec4d)

[Unreleased]: https://github.com/gesinn-it-pub/SimpleTooltip/compare/3.0.0...HEAD
[3.0.0]: https://github.com/gesinn-it-pub/SimpleTooltip/compare/2.0.1...3.0.0
