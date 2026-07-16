# BonusPerDefendingUnit
#// TS26_084 Fearless Attack (Event, cost 4, Heroism) — Attack with a unit; it gets +1/+0 for this attack
#// per unit the defending player controls. The opponent controls 2 space units, so SEC_080 (3 power) gets
#// +2 → 5 and hits the enemy base for 5.
## GIVEN
CommonSetup: bgw/rrk/{myResources:4;handCardIds:TS26_084}
WithP1GroundArena: SEC_080:1:0
WithP2SpaceArena: [SOR_237:1:0 SOR_225:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2BASEDMG:5
