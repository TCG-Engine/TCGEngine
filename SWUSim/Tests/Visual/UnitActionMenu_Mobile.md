# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the Attack / Ability menu on the PHONE layout in the browser.
#
# VISUAL CHECK — the Attack / Ability chooser is usable on a phone
#
#   Reported 2026-09-17 (game 505707): "can't attack with Poe on mobile — the ability/attack menu doesn't show".
#   Poe Dameron was attached as a pilot to Emissary's Sheathipede, so the vehicle had an Action and tapping it
#   opens the Attack / Ability chooser (#swuUnitActionMenu, built in GameLayoutShared.php). Its CSS lived only in
#   the desktop GameLayout.php, which phones never load, so the menu rendered unstyled at the top-left of the page,
#   hidden under the fixed top control band. The CSS now lives in GameLayoutShared.php.

## HOW TO RUN

Automated (Chromium / Firefox / WebKit, phone and desktop layouts; the fixture is imported as a replay, so
nothing changes on the board):
    cd DevTools/ui-harness && node unit-action-menu-mobile-xbrowser.mjs
Fixture: DevTools/ui-harness/fixtures/unit-action-menu-mobile.json (P1's Bail Organa SOR_094 ready, with an
Action). Screenshots: /tmp/unit-action-menu-<engine>-<mobile|desktop>.png

By eye: open any game on a phone (or add &swuLayout=mobile to the game URL) with a ready unit that has an Action,
e.g. a vehicle carrying Poe Dameron, or Bail Organa.

## WHAT TO LOOK AT

1. **Tap the glowing unit.** A small bordered menu with **Attack** and **Ability** appears just above the unit
   (below it if the unit is near the top), not at the top-left corner of the screen.
2. **Nothing covers it** — not the top control band, the hand, or the resource badge.
3. **Tap Attack.** The menu closes and the attack target prompt starts. **Tap Ability** on another unit: the
   unit's Action resolves.
4. **Tap elsewhere** with the menu open: it closes.
5. **Desktop** looks exactly as before (same menu, same place).
