# AttackWithStolenUnit
#// SWUSim Replay Schema
Change of Heart — stolen unit can immediately attack on P1's next turn

## GIVEN
CommonSetup: yrw/ggk
SkipPreGame: true
WithP1Hand: SOR_224
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Resources: 6

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# ReturnAtRegroupPhase
#// SWUSim Replay Schema
Change of Heart — stolen unit returns to owner at start of regroup phase

## GIVEN
CommonSetup: yrw/ggk
SkipPreGame: true
WithP1Hand: SOR_224
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 6

## WHEN
- P1>PlayHand:0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063

---

# StealGroundUnit
#// SWUSim Replay Schema
Change of Heart — steal ground unit (auto-resolve single target)

## GIVEN
CommonSetup: yrw/ggk
SkipPreGame: true
WithP1Hand: SOR_224
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 6

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_063
