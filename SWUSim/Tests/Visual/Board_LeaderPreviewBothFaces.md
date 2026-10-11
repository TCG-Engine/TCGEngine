# ⚠⚠ NOT A SCHEMA FILE — DO NOT LOAD THIS IN THE TEST SCHEMA EDITOR.
# It has no GIVEN/WHEN/EXPECT; the harness below seeds its own board.
#
# VISUAL CHECK — hovering a LEADER previews BOTH faces, side by side (owner, 2026-10-01)
#
#   Any leader — in a Leader zone, a deployed Leader unit, or attached as a PILOT; and in SWUDeck, the left card panel
#   and the faded identity banner above it — previews its leader side AND its other side at once,
#   front first, at one card scale (shared short edge), as SWUniversity's CardPreview.tsx does.
#   Code: Core/jsInclude.js — CardDetailLeaderFaces / CardDetailPairLayout / ShowLeaderFacesDetail (+ the hook in
#   ShowSubcardDetail for pilots). SWUSim + SWUDeck (folderPath gate); a leader is recognised from the client card
#   dictionary (Cardtype), never by probing art. SWUDeck banner: SWUDeck/Custom/GameLayout.php (hover on, clicks
#   swallowed by guardIdentityBannerClicks).

## HOW TO RUN
    cd DevTools/ui-harness && node swusim-leader-both-faces-xbrowser.mjs      # SWUSim (seeds its own board)
    cd DevTools/ui-harness && node swudeck-leader-both-faces-xbrowser.mjs     # SWUDeck (:3100, imports its own deck)
Chromium / Firefox / WebKit, desktop 1600x1000 and phone 390x844 (touch long-press). Screenshots: $SHOTS
(default /tmp/leader-faces). Then look by eye (Safari is the owner's check) on any live board.

## WHAT TO LOOK AT
1. **Leader zone (desktop hover).** After the usual hover delay, TWO cards appear side by side: the landscape
   leader side, then its back. Chancellor Palpatine (TWI_017) shows TWO landscape cards — Palpatine, then Darth
   Sidious — at the same height.
2. **Deployed leader unit.** Hovering the unit on the board (its tile is the Leader Unit side) shows the SAME pair,
   leader side FIRST, then the portrait Leader Unit side. The landscape card's HEIGHT equals the portrait card's
   WIDTH — they read as one card size, not one big and one small. The portrait card is 400px tall, as a single
   card preview always is.
3. **Ordinary cards are unchanged:** one card.
4. **Phone (long-press).** The two faces STACK (leader side on top), fit the screen, with the ✕ close button. There
   is no "See Leader Unit side" flip button any more — both sides are already showing.
5. **A leader with no back art** falls back to the old single-card preview of what was hovered.
6. **Pilot leader (SWUSim).** Hovering the pilot strip under a vehicle (e.g. Asajj Ventress on an X-Wing) shows the
   same pair, leader side first.
7. **SWUDeck card panel.** Hovering a leader tile (Leaders / Leader1 / Leader2 tabs) shows the pair BESIDE the tile.
8. **SWUDeck identity banner** (the faded leader/base art above the panel). Hovering the leader art shows the pair;
   the base shows one card. ⚠ CLICKING the banner must still do NOTHING — the leader stays in the deck. The phone
   layout's banner is decorative and has no preview.
