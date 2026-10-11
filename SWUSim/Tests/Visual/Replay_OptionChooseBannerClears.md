# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks a REPLAY in the browser.
#
# VISUAL CHECK — an answered OPTIONCHOOSE banner clears when a replay steps past it
#
#   Reported 2026-09-17: replaying a Goldfish game, "Choose a player to deal indirect damage — YOU / OPPONENT" stayed
#   on screen after the replay had applied "Opponent". The server replay was right; ClearSelectionMode() in
#   Core/UILibraries*.js never hid the OPTIONCHOOSE (or TWOSIDEDSLIDER) UI. A live game hides it on the click.

## HOW TO RUN

Automated (Chromium / Firefox / WebKit), imports the fixture replay through APIs/MatchReplay.php and steps it:
    cd DevTools/ui-harness && node replay-optionchoose-xbrowser.mjs
Fixture: DevTools/ui-harness/fixtures/replay-optionchoose-goldfish.json (Goldfish; Trap Field HMW_171 on P1's base;
play First Order Stormtrooper JTL_132 -> YES -> "Opponent"). Screenshots: /tmp/replay-optionchoose-<engine>.png

By eye: load that fixture from the main menu's replay library (or a real game you saved with the same shape) and
use the replay panel's Next button.

## WHAT TO LOOK AT

1. **Step 2 (after YES).** The bottom banner reads "Choose a player to deal indirect damage" with YOU and OPPONENT.
2. **Step 3 ("Opponent").** The banner disappears; the goldfish Echo Base shows 1 damage. Nothing is left floating.
3. **Reset, then Play All.** Same end board; no banner remains at the end.
4. **Other OPTIONCHOOSE prompts** (e.g. SOR_221 Outmaneuver "Ground / Space") behave the same way in a replay.
