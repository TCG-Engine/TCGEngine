# WhenPlayed_Deal2Ground
#// TWI_167 Heavy Persuader Tank (Unit 6/5, Ground, cost 7) — "Exploit 2. When Played: You may deal 2
#// damage to a ground unit." Played with no friendly units (Exploit auto-skips); the When Played may-deal
#// targets the enemy SOR_046 for 2.

## GIVEN
CommonSetup: rrk/grw/{myResources:7;handCardIds:TWI_167}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
