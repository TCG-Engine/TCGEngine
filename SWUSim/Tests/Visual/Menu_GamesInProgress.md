# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the MAIN MENU (SharedUI/Sites/SWUSim/MainMenu.php), which is not a gamestate.
#
# VISUAL CHECK — "Games in Progress", the main menu's left panel (owner, 2026-09-21)
#
#   One chip per PUBLIC SWUSim match in progress: each seat's leader over its base, "vs" between them, and a Spectate
#   button — modelled on the owner's reference screenshot. Twin Suns / Team Suns seats show TWO leaders side by side in
#   front of the base (the SWUDeck deck-list stack). Guests may spectate public games (Core/GameAuth.php).
#     Data:   SWUSim/PublicGames.php (SWUPublicGamesList) — active-game index + Match.json; 10s shared APCu cache.
#     Client: MainMenu.php swuGameChip / swuIdentityStack / swuRenderPublicGames; refreshed every 20s while visible.
#     Styles: css/swusim-menu.css "Games in Progress".
#   Not listed: private games, solo games (Goldfish / Hotseat / Arenabot — no match), finished matches, earlier games
#   of a Bo3, games idle for 30 minutes.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1672 / 1280 / 390px, fixture + one live public game):
    cd DevTools/ui-harness && node swusim-games-in-progress-xbrowser.mjs
Server rules + guest spectating with dev mode OFF:
    docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off -d apc.enable_cli=1 SWUSim/DevTools/tests/public_games_test.php
Screenshots: /tmp/games-in-progress-<engine>-<width>.png. For real data, queue two browsers (or two guests) into the
same PvP format, load a seat once, then open the menu in a third browser.

## WHAT TO LOOK AT

1. **Header.** "GAMES IN PROGRESS", the number of public games at the right, the square refresh button; under it a
   "Filter by Format" dropdown listing only formats that currently have games; then a thin rule.
2. **1v1 chip.** leader · VS · leader on ONE line, each leader in front of its base (the base peeks out top-right).
   Footer: the format in small caps on the left, SPECTATE on the right. Hover lifts the chip border to gold.
3. **Twin Suns chip.** One stack per seat — two leaders side by side in front of the base — all seats in a row (2×2
   for four), no "vs"; the footer reads "TWIN SUNS · 3 PLAYERS".
4. **Team Suns chip.** One team's two stacks, "VS", the other team's two stacks (seats 1+3 against 2+4).
5. **Hover a stack.** The tooltip names the leader(s) and the base.
6. **Filter.** Picking a format leaves only its chips; a format with no games shows "No games in this format"; the
   choice survives the 20s refresh while that format still has games.
7. **Empty.** No public games: the people icon, "No games in progress", and a Refresh button.
8. **Spectate.** Opens the game as a spectator in the same tab — also for a GUEST (no login page). Spectator chat
   stays read-only for guests.
9. **Long lists.** The list scrolls inside the panel (max 640px); art loads lazily.
10. **Phone (390px).** Chips stack full width; 1v1 stacks shrink so leader · vs · leader still fits one line.
