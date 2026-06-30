# SOR_039 AT-AT Suppressor (8/8, Ground) — When Played: Exhaust all ground units (both
# players, including itself). Two ready ground units (a friendly and an enemy Battlefield
# Marine) are both exhausted when the Suppressor enters. Space units are unaffected.

## GIVEN
CommonSetup: brk/brk/{myResources:12}
P1OnlyActions: true
WithP1Hand: SOR_039
WithP1GroundArena: SEC_080:1:0    # friendly ground unit (ready) — idx 0
WithP2GroundArena: SEC_080:1:0    # enemy ground unit (ready) — idx 0
WithP1SpaceArena: SOR_060:1:0     # friendly SPACE unit (ready) — must stay ready

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:READY
