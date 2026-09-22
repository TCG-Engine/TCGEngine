# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks site pages (Login, Signup, Previews, Profile), not a gamestate.
#
# VISUAL CHECK — Login, Signup, Previews and Profile in the "New Petranaki HUD" style (owner's name, 2026-09-21)
#
#   Their panels are three SHARED classes, restyled from SWUSim's stylesheet only (other sites use the same renderers):
#     .container.bg-black  — Login / Signup / Profile panes, Cosmetics, Game Settings
#     .container.bg-blue   — the cookie notice (Login) and the disclosures (Signup)
#     .card.ga-glass-card  — the Previews intro and each preview set
#   Styles: SharedUI/Sites/SWUSim/css/swusim-overrides.css, "Site pages … New Petranaki HUD" + the GLASS recipe.
#   Two PRE-EXISTING layout bugs were fixed in the same pass: Signup's form ran over the disclaimer footer (the auth
#   wrapper had a fixed height), and on phones Profile's first pane was clipped at 320px, hiding Change Password.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1440px and 390px; Profile logged in as claudebot1 / pass):
    cd DevTools/ui-harness && node swusim-site-pages-hud-xbrowser.mjs
Screenshots: /tmp/site-hud-<engine>-<width>-<page>.png.

## WHAT TO LOOK AT

1. **Every top-level panel is the glass** — frosted stone grey (headless Firefox draws no blur; check a real
   Firefox), cut top-left + bottom-right with gold glows, a soft shadow, no darker box showing through.
2. **No glass-in-glass.** Profile's sections (Welcome, Change Password, Saved Decks, Blocked Users) are plain sections
   inside their pane; Signup's two disclosures are sunken wells inside the Sign Up panel.
3. **Titles** in spaced uppercase (LOG IN, SIGN UP, PREVIEWS, COSMETICS, GAME SETTINGS…).
4. **Fields** are sunken wells with a gold focus ring; selects have the light chevron.
5. **Buttons**: steel chamfered (Block, Change Password…); a form's submit (Submit / Sign Up) is the gold primary.
   Discord (blue) and Patreon (orange) keep their brand colours.
6. **Previews**: the LEADERS / UNITS… tabs are the menu's tabs — gold rim on the active one.
7. **Signup**: the disclaimer footer sits BELOW the form, never behind it (1440 and 390).
8. **Profile on a phone**: the first pane shows the whole Change Password form (both fields + the button).
