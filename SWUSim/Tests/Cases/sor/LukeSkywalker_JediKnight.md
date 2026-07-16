# FriendlyDefeated_Minus6
#// SOR_051 Luke Skywalker — the "-6/-6 if a friendly unit was defeated this phase" branch. P1's
#// SOR_210 (4/3) attacks an AT-ST and dies (a FRIENDLY unit defeated this phase). P2 passes, then P1
#// plays Luke and targets the SECOND, undamaged AT-ST → -6/-6 for the phase → 0/1. (Luke can't target
#// the first AT-ST + the -6 there: it already took 4 combat damage, so -6 HP would defeat it.)

## GIVEN
CommonSetup: bbw/bbw/{myResources:7}
WithP1GroundArena: SOR_210:1:0
WithP1Hand: SOR_051
WithP2GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:POWER:0
P2GROUNDARENAUNIT:1:HP:1

---

# NoFriendlyDefeated_Minus3
#// SOR_051 Luke Skywalker (Unit 6/7, cost 7, Vigilance/Heroism, Restore 3) — "When Played: Give an
#// enemy unit -3/-3 for this phase. If a friendly unit was defeated this phase, give that enemy unit
#// -6/-6 for this phase instead." No friendly unit has been defeated this phase, so the basic -3/-3
#// applies. The single enemy (AT-ST, 6/7) auto-resolves → 3/4 for the phase.

## GIVEN
CommonSetup: bbw/bbw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SOR_051
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:4
