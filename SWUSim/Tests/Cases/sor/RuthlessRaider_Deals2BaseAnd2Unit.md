# SOR_134 Ruthless Raider (Space, cost 6) — When Played: deal 2 to an enemy base AND
# 2 to an enemy unit. P2's base takes 2; P2's only unit (Consular Security Force) is
# auto-chosen and takes 2.

## GIVEN
CommonSetup: rrk/rrk/{myResources:6;handCardIds:SOR_134}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2
