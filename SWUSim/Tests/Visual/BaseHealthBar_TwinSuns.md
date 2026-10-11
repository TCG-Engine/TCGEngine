# VISUAL CHECK — base health bars, Twin Suns Home view (tiles + your own base)
#
# Visual-only schema (Tests/Visual/ is not scanned by the regression endpoint).
# SETUP — load in the Test Schema Editor, or:
#   GN=$(curl -s -X POST http://localhost:3400/TCGEngine/SWUSim/TestSchemaSetup.php \
#          --data-urlencode "schema@SWUSim/Tests/Visual/BaseHealthBar_TwinSuns.md" | grep -o '"gameName":[0-9]*' | grep -o '[0-9]*')
#   open "http://localhost:3400/TCGEngine/NextTurn.php?folderPath=SWUSim&gameName=$GN&playerID=1&authKey=testschema"
#   PHONE: append &swuLayout=mobile at 430x932. Check Chromium, Firefox AND Safari/WebKit.
#
# FEATURE (owner, 2026-10-08) — spec docs/superpowers/specs/2026-10-08-swusim-base-health-bar-design.md
#   Aspect-coloured bar; dark grey grows from the RIGHT as damage comes in; steel-rimmed Petranaki depth.
#
# ── WHAT TO LOOK AT ─────────────────────────────────────────────────────────────────────────────
#   • P2 Security Complex: FULL blue bar (0 damage). P3 Lake Country: LIGHT GREY, only a sliver left
#     (30 of 34). P4 Starlight Temple: green, a bit over half (12 of 28).
#   • Each tile bar is THIN (3px desktop, 2px phone), on the card's top edge, starting exactly where the
#     blue HP badge ends and stopping before the aspect icon. The base TITLE is fully readable.
#   • The HP badge sits ON TOP of the bar's left end; the red damage token is unchanged in the centre.
#   • YOUR base (Administrator's Tower, 9 of 30): a 12px (phone 7px) yellow bar ABOVE the card, outside
#     it, the card's exact width, with a steel rim and cut corners (top-left, bottom-right).
#   • DEPTH: the colour has a lit top edge and a bright edge where it meets the grey; the grey looks sunken;
#     a soft shadow lifts the bar. A flat, rimless full-size bar is the old version.
#   • No bar on the opponent's side of the board (the tiles replace it). Zoom In on P4: their board shows
#     a green bar under Starlight Temple (not P2's base).
#   • TURN OFF HEALTH BARS (owner, 2026-10-08): gear menu → "Turn off health bars" (or Profile → Game Settings
#     when signed in). Every bar disappears at once — tiles AND your own base — while the blue HP badges on
#     the tiles stay. Reload: still off. Untick: the bars come back. Logged out it lives in this browser only.
#
# ⚠ HOW THIS REGRESSES:
#   • a tile rebuild without swuHbSettle makes the bars JUMP instead of slide;
#   • a bar emitted after the badge paints over it;
#   • a lost .swu-hb rule silently flattens it;
#   • sizing from the base <img> instead of the card element (#myBase-0) makes the bar ~16px too wide and
#     leaves a gap: the art is transform-scaled 1.1x inside the card, which clips it.
#   Automated: DevTools/ui-harness/swusim-base-health-bar-xbrowser.mjs (mutation-checked).

## GIVEN
#// P2's base comes from CommonSetup's theirBase — there is no WithP2Base directive.
CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; myBase:SOR_029; myBaseDamage:9; theirBase:SOR_019}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Base: JTL_031:30
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: LOF_024:12
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0
WithP4GroundArena: SOR_095:1:0

## WHEN

## EXPECT
TURNPLAYER:1
SEATCOUNT:4
