# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the MAIN MENU (SharedUI/Sites/SWUSim/MainMenu.php), which is not a gamestate.
#
# VISUAL CHECK — the game-setup dropdowns (docs/superpowers/specs/2026-09-16-swusim-format-menu-design.md)
#
#   Three dropdowns: Game type → Opponent / Players / Mode → Card pool. They write a hidden #swu-format-select and
#   #swu-cardpool-input; nothing else changed about what a game stores.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit):
    cd DevTools/ui-harness && node botpractice-menu-xbrowser.mjs
Screenshots land in /tmp/arenabot-menu-shots/. Then look, by eye, at the menu in each browser you have:
    http://localhost:3400/TCGEngine/SharedUI/Sites/SWUSim/MainMenu.php   (logged out, then logged in as claudebot1 / pass)

## WHAT TO LOOK AT

1. **Layout, 1400px and 420px.** The three dropdowns sit on one row at desktop width and wrap cleanly on a phone. The
   Match Type sits on its own row below. Nothing overflows the "Create a New Game" card.
2. **Opening state, logged out AND logged in (owner, 2026-09-21).** The menu opens on Constructed → Arenabot (beta) →
   Premier. The Opponent dropdown lists Arenabot (beta) first, then PvP. Login changes nothing: a guest sees Constructed,
   Twin Suns and 1P Mode, and PvP offers every pool.
3. **Constructed → Arenabot → Premier.** "Bot deck link:" and "Bot play style" appear; the button reads "Start Arenabot";
   Match Type is Bo1 and greyed; no Create Private Room, no Join Queue.
4. **Arenabot → Premier with an SOR deck.** Start shows a red "Premier format error" line in the card and stays on the menu.
5. **1P Mode.** The Card pool dropdown disappears. Hotseat shows "Player 2 deck link (Hotseat):" and "Start Hotseat Game";
   Goldfish shows "Start 1P Game".
6. **Logged in → Twin Suns.** Players: Free-for-all, Teams. Both offer Standard and Preview. Match Type is Bo1 and greyed
   for all four; Create Private Room is shown; **Join Queue IS shown** (owner, 2026-09-20 — the Twin Suns family now takes
   public queues, as a public ROOM rather than a quick match). Clicking it lands on the WaitingRoom page, seated, with
   four seats drawn and a Start control, rather than opening the "waiting for opponent" popup.
7. **Invite.** Create a Premier private room, copy the invite link, open it in a private window (logged out). The three
   dropdowns show Constructed / PvP / Premier and are greyed; only Join Private Invite is offered. The invite notice names
   the format.
8. **Theme.** The new dropdowns use the same `swu-queue-select` style as Match Type in every theme.
9. **Join Queue (public queues, 2026-09-16; Twin Suns added 2026-09-20).** Logged in, Constructed → PvP shows Join Queue
   for all seven pools, and Twin Suns shows it on BOTH branches (Free-for-all and Teams, Standard and Preview — four
   formats). Arenabot and 1P Mode never show it. Logged out, the same.
10. **A real pairing.** claudebot1 in Chromium and claudebot2 in Firefox both pick Constructed → PvP → Premier, Bo1, with a
    Premier-legal deck (e.g. SWUSim/Tests/BotFixtures/meta-2026-09/aggro_vader_yellow.txt without its # lines) and click
    Join Queue. The first sees the waiting popup; the second's click lands both in the SAME game.
11. **An illegal deck.** PvP → Premier with an SOR list: Join Queue shows the format errors in red under the buttons and
    no waiting popup opens.
