# ⚠⚠ NOT A SCHEMA FILE — a Playwright-driven visual check of the "Export SWUPGN" button on the end-game screen.

## HOW TO RUN
cd DevTools/ui-harness && node swupgn-export-xbrowser.mjs        (ENGINES=chromium,firefox,webkit, LAYOUTS=desktop,mobile by default)
Each run creates its games through the real queue (local dev server): per engine × layout, one PvP game (two anonymous
seats paired by the Open Bo1 queue, conceded from seat 1's board) and one Arenabot game.
Screenshots: DevTools/ui-harness/_shots/swupgn-export-<engine>-<desktop|mobile>.png (taken 1.5 s after the overlay opens)

## WHAT TO LOOK AT
1. The end-game panel reads, top to bottom: the title; the button stack (Return to Main Menu … Report Bug); the save row
   ("Save this completed match replay to this browser." with SAVE REPLAY and EXPORT SWUPGN side by side); then the stats.
   On desktop the save row sits under the button stack in the left column and the stats fill the right column.
2. Nothing overlaps: the save row's caption is not under Convert to Best of 3 / Report Bug, and on a phone neither
   SAVE REPLAY nor EXPORT SWUPGN is under the button stack (before this change the stack covered both on phones).
3. Clicking EXPORT SWUPGN downloads petranaki_<your leader>_vs_<their leader>_<date>.swupgn — your own view
   ([Perspective "P1"], [Seed "unseeded"]) — and the main menu's Replays tab then lists it ("<leader> vs <leader>",
   Result, date, "from petranaki", Play SWUPGN / Delete).
4. An Arenabot game is not recorded: EXPORT SWUPGN turns into a disabled "Replay file unavailable" when clicked.
5. Spectators, replays (replay=1) and SWU-PGN viewer games get no button.
6. A 3–4 seat Twin Suns end screen is unchanged: its save row still sits in the stats cell (it has no stats table).
