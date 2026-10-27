# Bundled third-party code

## Tooltipster 3.3.0

- Files: `jquery.tooltipster.js`, `tooltipster.css`
- Source: <https://github.com/iamceege/tooltipster> (tag `3.3.0`, released 2014-11-08), MIT license
- Changes: none to the code. Line endings are LF, and trailing whitespace was removed from the CSS.
- Status: the project is no longer maintained (last release 4.2.8 in 2020). No security fixes will be
  published upstream and none are known at the time of writing, so the extension does not rely on the
  library for safety: `SimpleTooltip.js` builds the tooltip content from sanitized, inert HTML
  (no scripts, event handlers or script URLs) before it is handed to the library.
- Decision: kept as is. Replacing it is a separate change because `mw.libs.SimpleTooltip.defaultOptions`
  exposes Tooltipster options.

## Fugue Icons

`information.png` and `question.png`: <https://github.com/yusukekamiyamane/fugue-icons>
