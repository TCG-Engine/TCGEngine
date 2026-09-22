# ⚠⚠ This is a VISUAL CHECK, not a schema test — the GIVEN below is the board the harness loads.
# It checks Twin Suns seat NAMES (owner request 2026-09-22: "people just see P1 P2 P3 P4 and they want to see actual
# usernames" — on the home previews, and "username (PN):" in chat; 1v1 does not need it).
#
#   1. Home previews (desktop .swu-home-strip / mobile .swu-seat-row): the seat label is swuSeatLabelHtml() —
#      "username P3", the name in full weight and the seat small and dimmed after it. A long name is truncated
#      with an ellipsis (desktop 140px, mobile 84px); hovering shows "username (P3)". A guest seat has no
#      username (SWU_SEAT_USERNAMES holds real accounts only) and keeps the bare "P3".
#   2. Chat: at 3+ seats a message is labelled "username (P3):" — also the whisper checkboxes and the
#      "… whispered something to …" stub. window.CHAT_SEAT_SUFFIX turns it on (GameLayoutShared.php); 1v1
#      leaves it off and keeps "username:". Other sims never set it.
#   3. Everywhere else a seat is named (swuSeatName → "username (P3)", bare "P3" for a guest):
#      - the GAME LOG, in 1v1 TOO (owner 2026-09-22) — its lines are written server-side as "P2 took the blast
#        counter" and renamed at render (swuNameSeatsInLog; card links [[id|name]] untouched; names escaped).
#        1v1 uses the bare username ("claudebot1 took the initiative"); a guest is "Guest P2" (the server's
#        SWU_SEAT_DISPLAY_NAMES, the site-wide pattern); the bot seat is "Arenabot"; a game with no match
#        record keeps "P2" except for a logged-in viewer's OWN seat, which the session names;
#      - the matchup view labels ("vs username (P3)", a teammate without "vs");
#      - the Zoom tooltips ("Open your board vs username (P3)");
#      - an eliminated player's read-only badge ("viewing username (P2) vs username (P3)").
#
# ⚠ A TestSchemaSetup game has NO match record, so no seat gets a username from the server (see the NAMES note in
#   WhisperChat_TwinSuns.md). The harness therefore checks the real flags and the guest fallback as served, then
#   serves the page with SWU_SEAT_USERNAMES filled in (a route rewrite), which is the shape a match game loads with. To see server-supplied names, play a real 3-4 seat lobby game
#   with logged-in accounts (claudebot1-4).

## HOW TO RUN
    cd DevTools/ui-harness && node swusim-seat-usernames-xbrowser.mjs
Screenshots: /tmp/seat-names-<engine>-desktop.png, /tmp/seat-names-<engine>-mobile.png.

## WHAT TO LOOK AT
1. Each opponent tile starts "name P3": the name bright, "P3" smaller and dimmer. The long name ends in "…" and
   does not push the leaders, base or Zoom button out of line.
2. The phone rows: same label, shorter truncation, no horizontal page scroll.
3. Chat rows read "name (P3): …".
4. Game log lines read "name (P2) took the blast counter …"; a card link inside a line is unchanged.
5. Hovering Zoom in: "Open your board vs name (P3)".
6. A 1v1 game's log: "name took the initiative", "Guest P2 played …", and "Arenabot …" in a bot game.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Base: SOR_026:5
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: SOR_026:8
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010

## WHEN

## EXPECT
TURNPLAYER:1
