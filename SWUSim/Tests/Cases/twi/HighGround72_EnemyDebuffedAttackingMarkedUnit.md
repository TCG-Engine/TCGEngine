# TWI_072 I Have the High Ground (Event, cost 1, Vigilance) — "Choose a friendly unit. Each enemy unit
# gets -4/-0 while attacking that unit this phase." P1 marks SOR_046 (3/7); P2's SEC_080 (3 power)
# attacks it at power 3-4 = 0 → SOR_046 takes 0 damage, while SEC_080 dies to the 3-power counter.

## GIVEN
CommonSetup: bbw/grw/{myResources:1;handCardIds:TWI_072}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0
