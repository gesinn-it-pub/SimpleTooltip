# Changelog

## [Unreleased]

### Changed

- Wikitext is now supported everywhere: in the tooltip text of `#tip-text`, `#tip-info` and `#tip-img`, and
  in the text that is hovered over in `#tip-text`.
- `#tip-img` takes a file name of this wiki (with or without `File:`) instead of a path. External images
  are accepted only as http(s) URLs and only if the wiki allows external images (`$wgAllowExternalImages`
  and `$wgAllowExternalImagesFrom`); otherwise an error message is shown. Images get an empty `alt`.
- `"` in tooltip text is no longer replaced by `'`; the attribute is escaped properly instead.

### Removed

- The unused legacy i18n shim `SimpleTooltip.i18n.php` (it registered a function that did not exist), the
  obsolete `targets` ResourceLoader setting and the unused `SimpleTooltipSubmitText` setting.

### Fixed

- `{{#tip-text: a}}` and `{{#tip-img: url}}` without a tooltip text no longer raise a PHP warning, and the
  tooltip text `0` is no longer treated as empty.
- Security: tooltip text was HTML-decoded a second time in the browser, so escaped markup (for example
  `&lt;img onerror=...&gt;` or `<nowiki>`-wrapped HTML) was turned into live elements. The extra decoding
  step is gone and the tooltip HTML is additionally stripped of scripts, event handlers and script URLs.
- Tooltips are now initialised through the `wikipage.content` hook instead of a search of the whole document,
  so they also work in content that is added later (live preview, AJAX). Elements that already have a tooltip
  are skipped.
- `mw.libs.SimpleTooltip.defaultOptions` and caller-supplied options are no longer modified by `trigger()`.
