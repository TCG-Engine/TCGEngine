# VISUAL CHECK — named Action abilities: the leader menu (exhausted leader)
#
# Visual-only schema. Run DevTools/ui-harness/swusim-named-actions-xbrowser.mjs (it builds this board too).
#
# Admiral Trench is EXHAUSTED: his Action is listed GREYED ("Exhausted") — owner decision: a greyed Action stays
# visible — and with too few resources to deploy, there is no Deploy button.

## GIVEN
CommonSetup: ggw/ggw/{myLeader:JTL_014:0;myResources:2}
SkipPreGame: true
WithActivePlayer: 1

## WHEN

## EXPECT
P1ACTIONENTRIES:myLeader-0:DISABLED:JTL_014.0
