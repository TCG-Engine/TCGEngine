# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks localized card IMAGES in the browser.
#
# VISUAL CHECK — Card language (Spanish / Italian / French card images)
#
#   A player can show card images in es / it / fr (images only; names, logs and prompts stay English).
#   Core/SWUCardI18n.js rewrites SWU card-art URLs to AppCore/SWU/Images/i18n/<lang>/ when that language's
#   manifest lists the file, and falls back to English otherwise.
#   Design: docs/superpowers/specs/2026-09-17-swusim-card-i18n-images-design.md

## SETUP

Generate the language's images first (generator admin page → SWUSim → "Localized card images", or
`zzCardI18nImageGenerator.php rootName=SWUSim locale=es`). For a quick check, the automated harness prints a
command that generates only the cards on its test board:
    cd DevTools/ui-harness && ENGINES=chromium LIST=1 node card-language-xbrowser.mjs

## HOW TO RUN

Automated (Chromium / Firefox / WebKit):
    cd DevTools/ui-harness && node card-language-xbrowser.mjs
Screenshots: /tmp/card-language-es-<engine>.png, /tmp/card-language-en-<engine>.png

## WHAT TO LOOK AT

1. **Gear menu → Card language → Español.** Hand, arena units, leaders (both sides), bases and resources switch to
   Spanish images without a reload. Cards with no Spanish image (preview/mock art, tokens without a translation)
   stay English, with no broken images.
2. **Hover a card.** The large preview is Spanish too. Flip a leader's preview: both faces are Spanish.
3. **Popups** (a search/scry panel, a discard pile popup, the Fortify badge popup): Spanish where available.
4. **Reload.** The language is remembered in this browser.
5. **Card language → English.** Every image returns to English, without a reload.
6. **Profile → Game Settings → Card language** (logged in). Changing it saves ("Saved.") and a game opened in another
   browser logged in as the same account starts in that language, unless that browser has its own choice.
7. **Sideboard screen** (Bo3). Deck and sideboard tiles follow the chosen language.
8. **The opponent** is unaffected: their browser shows their own language.
