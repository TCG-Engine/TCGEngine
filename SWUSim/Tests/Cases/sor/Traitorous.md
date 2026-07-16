# NoStealUnit4PlusCost
#// SWUSim Replay Schema
Traitorous — attach to non-leader unit costing 4+, no steal trigger

## GIVEN
CommonSetup: grw/ggk
SkipPreGame: true
WithP1Hand: SOR_122
WithP2GroundArena: SOR_148:1:0
WithP1Resources: 5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_148
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# ReturnWhenUpgradeDefeated
#// SWUSim Replay Schema
Traitorous — when upgrade is defeated, unit returns to its owner

## GIVEN
CommonSetup: grw/ggk
SkipPreGame: true
WithP1Hand: SOR_122
WithP2Hand: SOR_251
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 5
WithP2Resources: 3

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2RESCOUNT:3
P2RESAVAILABLE:2

---

# StealUnit3Cost
#// SWUSim Replay Schema
Traitorous — attach to non-leader unit costing 3 or less, take control of it

## GIVEN
CommonSetup: grw/ggk
SkipPreGame: true
WithP1Hand: SOR_122
WithP2GroundArena: SOR_063:1:0
WithP1Resources: 5

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_063
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
