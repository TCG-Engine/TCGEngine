# ⚠⚠ NOT A SCHEMA FILE — a Playwright-driven visual check of the SWU-PGN replay viewer and the Replays tab.

## HOW TO RUN
cd DevTools/ui-harness && node swupgn-viewer-xbrowser.mjs        (ENGINES=chromium,firefox,webkit by default; PART=viewer|menu for one half)
Fixtures: DevTools/tdd-regression/fixtures/swupgn/viewer-game.swupgn, viewer-game-p1.swupgn
          (regenerate with DevTools/tdd-regression/fixtures/make_swupgn_viewer_fixtures.php)
⚠ A full run opens ~24 viewer games; one client may open 60 per 10 minutes (SwuPgnViewerLimits.php), so two
  back-to-back full runs plus test_swupgn_viewer_api.php can get HTTP 429 — wait a few minutes.
Screenshots: /tmp/swupgn-viewer-<engine>-<desktop|mobile>.png, /tmp/swupgn-replays-<engine>-<desktop|mobile>.png

## WHAT TO LOOK AT
1. Desktop: the replay controls are docked in the right sidebar, clear of the board and of both hands. Phone: the controls
   are a bar at the bottom, and the board scrolls clear of it.
2. No "Waiting for the other player" pill and no dimming over the board.
3. At the last step: Vanguard Infantry (ground) is exhausted at 3/4 and carries Craving Power, a Shield and an Advantage;
   the A-Wing (space) is exhausted at 4/4 and carries Academy Graduate; Darth Vader is deployed (5/8) and holds Director
   Krennic captive; Jedha City shows 3 damage.
4. The unknown card is a card back with "Mystery <b>Card</b>" written as plain text — the angle brackets visible.
5. Captions read like the story: "Player 1 attacks Player 2's base with Vanguard Infantry → 3 damage to Player 2's base — 22 HP left".
6. "Hands: P2's view" reloads the board from P2's seat, with P1's hand as card backs.
7. The P1-perspective file has no Hands control, and P2's hand is card backs. In any one seat's view, the captions
   never name the other seat's draws, resources or search finds ("Player 2 resources a card").
7b. A file whose leader/base SWUSim does not know shows them as named card backs from the very first paint (the
   harness delays the viewer's info call by 3 s to prove it). A file whose keyframes disagree with its events
   still plays, and the ⓘ button reads "ⓘ ⚠ 1".
8. Replays tab: Open .swupgn, then rows with "<leader> vs <leader>", the result, the date and the source; a leader name
   containing markup shows as text. Play SWUPGN reopens it (the same viewer game while it lives — no new upload);
   Delete removes the row. The "No replays yet" note is gone
   once a row exists, and nothing in a row is clipped on a phone.
Check Chromium, Firefox AND Safari/WebKit.
