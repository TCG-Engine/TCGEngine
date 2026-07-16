# EnemyCapturesFriendly_Heal6
#// SEC_068 Lando Calrissian (Ground, 6/8, Vigilance, cost 7) — Grit + When Played: choose an enemy unit
#//   and another friendly non-leader unit → heal 6 from your base and the enemy unit captures the friendly.
#// Enemy SOR_046 captures friendly SOR_095; P1 base heals 6 (6→0).

## GIVEN
CommonSetup: bbk/rrk/{myResources:7;myBaseDamage:6}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_068

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_068
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION
