# Changelog

## [Unreleased]

### Fixed

- `{{#tip-text: a}}` and `{{#tip-img: url}}` without a tooltip text no longer raise a PHP warning, and the
  tooltip text `0` is no longer treated as empty.
- Security: tooltip text was HTML-decoded a second time in the browser, so escaped markup (for example
  `&lt;img onerror=...&gt;` or `<nowiki>`-wrapped HTML) was turned into live elements. The extra decoding
  step is gone and the tooltip HTML is additionally stripped of scripts, event handlers and script URLs.
- `mw.libs.SimpleTooltip.defaultOptions` and caller-supplied options are no longer modified by `trigger()`.
