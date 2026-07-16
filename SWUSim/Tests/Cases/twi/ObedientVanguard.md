# WhenDefeated_BuffTrooper
#// TWI_104 Obedient Vanguard (Unit 1/1, Ground) — "Raid 1. When Defeated: You may give a Trooper unit
#// +2/+2 for this phase." TWI_104 attacks SOR_046 (3/7) and dies to the counter; its When Defeated buffs
#// the friendly Trooper SOR_095 → 5/5. (After TWI_104 is gone, SOR_095 is at index 0.)

## GIVEN
CommonSetup: ggw/rrk/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_104:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
