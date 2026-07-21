# DefeatedThenExp
#// LOF_263 Last Words — If a friendly unit was defeated this phase, give 2 Experience tokens to a unit. P1
#// first plays LOF_264 to defeat its own SOR_059 (setting the "friendly defeated" flag), then LOF_263 gives
#// Plo Koon 2 Experience → 8/10.

## GIVEN
CommonSetup: ggk/rrw/{myResources:9;handCardIds:LOF_264,LOF_263}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:HP:10

---

# EnemyDefeatDoesNotCount
#// LOF_263 Last Words — the condition is that a FRIENDLY unit was defeated this phase. P1 uses LOF_264
#// (It's Worse) to defeat an ENEMY unit (SOR_059), then plays Last Words; because no friendly unit was
#// defeated, no Experience is given and the friendly Plo Koon (LOF_050) stays at 6/8 with no upgrades.

## GIVEN
CommonSetup: ggk/rrw/{myResources:9;handCardIds:LOF_264,LOF_263}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:8
