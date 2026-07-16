# OnAhsoka_MayAttack
#// TWI_248 Ahsoka's Padawan Lightsaber (Upgrade +2/+0, cost 1, Heroism, non-Vehicle) — "When Played: If
#// attached unit is Ahsoka Tano, you may attack with a unit." Played on TWI_194 (Ahsoka Tano; SOR_237 is a
#// Vehicle so it's the only valid host), P1 attacks with SOR_237 for 2 to the enemy base.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;handCardIds:TWI_248}
P1OnlyActions: true
WithP1GroundArena: TWI_194:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_194
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2BASEDMG:2
