# WhenPlayed_HealAndSelfDamage
#// TWI_044 Kashyyyk Defender (Unit 0/5, Ground) — "Grit. When Played: Heal up to 2 damage from another
#// unit and deal that much damage to this unit." Played with a damaged friendly SOR_046 (2 damage);
#// heals it 2 (to 0) and takes 2 self-damage → Grit makes the Defender 2 power.

## GIVEN
CommonSetup: bbw/grw/{myResources:2;handCardIds:TWI_044}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:2

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:TWI_044
P1GROUNDARENAUNIT:1:DAMAGE:2
P1GROUNDARENAUNIT:1:POWER:2
