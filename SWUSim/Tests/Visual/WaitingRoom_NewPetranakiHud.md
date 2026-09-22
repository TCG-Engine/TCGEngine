# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT. It checks the WAITING ROOM page (SharedUI/Render/WaitingRoom.php), not a gamestate.
#
# VISUAL CHECK — the SWUSim waiting room in the "New Petranaki HUD" style (owner's name, 2026-09-21)
#
#   Same page, same markup, same behaviour — restyled to match the main menu: frosted stone-grey glass panel with gold
#   corner glows, spaced-uppercase title, sunken seat wells, sunken deck field, chamfered steel buttons with a gold Start.
#   Styles: SharedUI/Sites/SWUSim/css/swusim-overrides.css, "Waiting room … New Petranaki HUD" (+ the shared GLASS
#   recipe at the top of that file). The renderer is shared with FaBSim; only SWUSim loads that file, so FaBSim's room is
#   untouched. Everything is scoped under #wr-root / `.row-wrapper > .card.wr-panel`, because the renderer prints its own
#   <style> after the site CSS.

## HOW TO RUN

Automated half (Chromium / Firefox / WebKit, 1728px and 390px, real rooms of 1 and 3 players):
    cd DevTools/ui-harness && node swusim-waitingroom-hud-xbrowser.mjs
Behaviour is still covered by waiting-room-xbrowser.mjs and swusim-public-room-xbrowser.mjs.
Then by eye: create a private Twin Suns room from the menu, join it from two private windows with the invite link;
also a Team Suns room (Players → Teams).

## WHAT TO LOOK AT

1. **Panel.** Frosted glass (the arena art blurred through it — ⚠ headless Firefox draws NO blur, check a real
   Firefox), cut top-left + bottom-right with gold glows, a soft shadow, no darker box showing through the glass.
2. **Head.** "ROOM" / "TEAM ROOM" in spaced uppercase; Leave top-right; "Invite: <code>" with Copy Invite Link.
3. **Seats.** Sunken wells, 2×2. YOUR seat has a gold rim + faint glow; an empty seat is dashed with "Waiting…".
   "SEAT N" is a small spaced label, the player name bold under it, the identity strip below, then the pills: READY
   (green), NOT READY (gold), NO DECK (red), boxed. Remove is a small steel chip.
4. **Team Suns.** RED / BLUE headers in their team colours, readable (bigger, bolder than before).
5. **Deck bar.** Saved-deck dropdown (logged in) and the deck field are sunken wells with a gold focus ring; Change
   Deck / Ready are steel chamfered buttons at the same 42px height.
6. **Start.** Disabled: plain grey. Enabled (3+ players, all ready): gold rim, gold text, soft gold glow — the loudest
   thing on the page.
7. **Status strip.** A sunken strip: people icon, "N/4", and the hint.
8. **Phone (390px).** One column; buttons wrap; Start full width; no sideways scroll.
