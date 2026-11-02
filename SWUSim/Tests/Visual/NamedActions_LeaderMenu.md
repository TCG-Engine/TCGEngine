# VISUAL CHECK — named Action abilities: the leader menu (ready leader)
#
# Visual-only schema. Run DevTools/ui-harness/swusim-named-actions-xbrowser.mjs (it builds this board too).
#
# Clicking Admiral Trench (ready, 6 resources) opens the leader menu: his Action named after him ("Admiral Trench"),
# then the unchanged "Deploy Leader" (his deploy Action) and Cancel.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_014;myResources:6}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [SOR_046]

## WHEN

## EXPECT
P1ACTIONENTRIES:myLeader-0:ENABLED:JTL_014.0
