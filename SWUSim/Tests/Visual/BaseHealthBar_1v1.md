# VISUAL CHECK — base health bars, 1v1 board (desktop and phone)
#
# Visual-only schema. SETUP as BaseHealthBar_TwinSuns.md, with this file. PHONE: &swuLayout=mobile.
# Check Chromium, Firefox AND Safari/WebKit.
#
# ── WHAT TO LOOK AT ─────────────────────────────────────────────────────────────────────────────
#   • DESKTOP: a green bar UNDER the opponent's Command Center and a red bar OVER your Catacombs of
#     Cadera, both 12px, both 70% full (9 of 30), FACING EACH OTHER across the middle, 4px from each
#     card. The gap between the two bases is wider than before (the bars sit in it); nothing overlaps a
#     card or the buttons.
#   • PHONE: the two bases sit side by side in the middle band; a 7px bar sits just ABOVE each one.
#     The base and leader cards in that row still line up.
#   • Each bar is the card's exact width, with the steel rim, cut corners and lit edges.
#   • SHORT WINDOW: at 1366x768, and at 1280x660 (the centre column switches to a leader-beside-base
#     ROW below 680px tall), each bar still follows its own base and covers nothing.

## GIVEN
CommonSetup: rrk/bbw/{myLeader:IBH_053; theirLeader:SHD_007; myBase:SOR_026; myBaseDamage:9; theirBase:SOR_023; theirBaseDamage:9}
SkipPreGame: true
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN

## EXPECT
TURNPLAYER:1
