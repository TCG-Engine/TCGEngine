# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the MAIN MENU (SharedUI/Sites/SWUSim/MainMenu.php), which is not a gamestate.
#
# VISUAL CHECK — Arenabot's GROUPED bot pre-cons (owner, 2026-10-01)
#
#   The flat "Bot Pre-Cons" well became one BLOCK per group — "ASH Meta September 2026" and "Force Fam HMW
#   Predictions" — and each block opens a PICKER dialog on top of the Arenabot modal with that group's decks.
#   Groups are fixture directories, defined once in SWUSim/DevTools/regen-deck-labels.php (SWU_PRECON_GROUPS) and
#   carried to the menu by SWUSim/Custom/BotDeckLabels.json. Markup: MainMenu.php (Arenabot fieldset, the nested
#   dialog.pcpick per group); JS: PRECON_GROUPS in MainMenu.php; styles: the "GROUPED pre-cons" section of
#   SharedUI/Sites/SWUSim/css/swusim-menu-2.css. Owner, same day: NO deck is picked by default, and opening
#   Arenabot puts the cursor in the empty Deck Link box.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1440px and 420px):
    cd DevTools/ui-harness && node swusim-precon-groups-xbrowser.mjs
Screenshots: $SHOTS (default /tmp/precon-groups) — <engine>@<width>-1-blocks / -2-picker / -3-picked.png.
Then look, by eye (Safari is the owner's check), at:
    http://localhost:3400/TCGEngine/SharedUI/MainMenu.php   → Arenabot

## WHAT TO LOOK AT

1. **On open.** The text cursor is in the empty **Deck Link** box. Under "Bot Pre-Cons" the note reads
   "32 tuned decks in 2 groups. Pick a group to choose one." and two blocks sit side by side (stacked at phone
   width): **ASH Meta September 2026 · 27 decks** and **Force Fam HMW Predictions · 5 decks**, each with a `›`.
   Neither is gold and neither says "Selected" — nothing is picked yet.
2. **The blocks are the pre-con row's material.** Chamfered corners (top-left, bottom-right), no rounded corners,
   no 1px border, left-aligned mixed-case text. ⚠ A centred or UPPERCASE label, or square corners, means the legacy
   `button` rules won the cascade (see legacy-css notes in MainMenu_SetupModals_RealData.md §17).
3. **A block opens its picker ON TOP.** The Arenabot modal stays visible, dimmed, behind it. The picker shows
   "BOT PRE-CONS" over the group name, "N decks. Pick one for the bot to play.", the deck rows (name, leader /
   base · cards, archetype tag) and a Done button. Desktop: all 5 HMW decks show with no scrolling. Phone width:
   a bottom sheet.
4. **Authors.** The HMW rows read "Ahsoka Tano (ASH) Yellow By Ninin" and "… By Star Wars Dad" (the fixture's
   `# Author:` line). ASH-meta rows have no "By".
5. **Picking.** Click a row: the picker closes, the block that holds it turns gold and reads
   "Selected: <deck>", the other block clears, the status line says "Playing the <deck> pre-con as the bot's
   deck." and Bot Style switches to the deck's archetype (Ninin's Ahsoka → Hyper Aggro).
6. **Keyboard.** Arrow keys move the choice inside the picker WITHOUT closing it; Done, the X or Escape close
   it. Escape closes only the picker — Arenabot stays open.
7. **Backdrop.** Clicking the dim area outside the picker closes only the picker.
8. **Closing Arenabot** (Cancel, X, Escape on Arenabot, or Start) never leaves a picker floating.
