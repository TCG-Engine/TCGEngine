# VISUAL CHECK — named Action abilities: the base menu
#
# Visual-only schema. Run DevTools/ui-harness/swusim-named-actions-xbrowser.mjs (it builds this board too).
#
# Clicking your base opens the same menu as a unit: one button per base Action. Security Complex (SOR_019) has already
# spent its Epic Action, so it is GREYED ("Used this game"); Carbonite Chamber's Action (a Fortify upgrade) is usable.
#   myBase-0 → Security Complex (greyed "Used this game") · Carbonite Chamber
#
# What to look at:
#   • The base menu looks like the unit menu; the greyed entry is dimmed with its reason; clicking it does nothing.

## GIVEN
CommonSetup: gbk/bbk/{myBase:SOR_019;theirBase:SOR_021;myBaseEpicUsed:1}
SkipPreGame: true
WithActivePlayer: 1
WithP1BaseUpgrade: HMW_095
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN

## EXPECT
P1ACTIONENTRIES:myBase-0:EXACT:SOR_019.0&HMW_095.0
P1ACTIONENTRIES:myBase-0:DISABLED:SOR_019.0
