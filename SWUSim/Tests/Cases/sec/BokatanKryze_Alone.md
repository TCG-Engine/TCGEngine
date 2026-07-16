# EnemyDefeated_GiveExp
#// SEC_051 Bo-Katan Kryze — "When an enemy unit is defeated: give an Experience token to a friendly unit."
#//   SOR_095 kills SOR_128; Bo-Katan's reaction gives an Experience token to SOR_095.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_051:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_051
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# WhenPlayed_EnemyMinus33
#// SEC_051 Bo-Katan Kryze (Ground, 8/8, cost 9) — When Played: give each enemy unit -3/-3 for this phase.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_051

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:0:HP:4
P1NODECISION
