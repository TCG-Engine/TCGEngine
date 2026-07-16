# WhenDefeated_Deal2ToBase
#// TWI_131 OOM-Series Officer (Unit 2/1, Ground, cost 2, Aggression/Villainy, Separatist/Droid) — "When
#// Defeated: Deal 2 damage to a base." It attacks SOR_046 (3/7), dealing 2 and dying to the 3 counter-
#// damage; its When Defeated then deals 2 to the chosen (enemy) base.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1}
P1OnlyActions: true
WithP1GroundArena: TWI_131:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2
