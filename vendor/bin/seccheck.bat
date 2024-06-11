@ECHO OFF
setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/../mediawiki/phan-taint-check-plugin/scripts/seccheck
SET COMPOSER_RUNTIME_BIN_DIR=%~dp0
sh "%BIN_TARGET%" %*
