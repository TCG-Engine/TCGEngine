# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the generator admin page (zzCodeGeneratorMain.php) in the browser.
#
# VISUAL CHECK — separate "Localized card images" section + set-scoped image replacement
#
#   overwriteImages=1 replaces every image; overwriteImages=HMW replaces only that set's images
#   (HMW_001, HMW_001_back, HMW_T01, mock_HMW_004). Shared rule: ImageOverwriteSpec() /
#   ImageOverwriteRequested() in zzImageConverter.php, used by zzCardCodeGenerator.php and
#   zzCardI18nImageGenerator.php. Anything else (e.g. "HMW,IC27") stops the run with an ERROR line.

## HOW TO RUN

Automated (Chromium / Firefox / WebKit; generator requests are stubbed, nothing is downloaded):
    cd DevTools/ui-harness && node generator-i18n-option-xbrowser.mjs
Screenshots: /tmp/generator-i18n-swusim-<engine>.png
Logic test: DevTools/tdd-regression/test_image_overwrite_scope.php

## WHAT TO LOOK AT (select SWUSim)

1. **Card generator options** says it applies to the "Card data & images" step (English card data and images) and
   holds Fetch current source data, Replace existing images, and an "Only this set" field.
2. **Localized card images** is its own section (SWUSim only) with Language, "Replace existing localized images" and
   its own "Only this set" field. Select another app: the section disappears.
3. Each "Only this set" field is greyed out until its Replace box is ticked, is 34px tall like the other controls, has
   no dropdown arrow, and shows what you type in capitals.
4. Run "Card data & images" with Replace ticked and HMW typed: the run log header reads `overwriteImages=HMW`.
   ⚠ English art is only downloaded on a "Fetch current source data" run (preview mock art is handled on every run).
5. Type something invalid (e.g. `HMW,IC27`) and run: the step fails with
   "overwriteImages must be 1 or a single set code such as HMW".
