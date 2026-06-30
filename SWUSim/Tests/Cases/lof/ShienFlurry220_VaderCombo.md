# LOF_220 Shien Flurry + LOF_037 Darth Vader combo (ybk: Cunning base / Iden Versio leader, so Shien Flurry
# and Vader are both on-aspect). Shien Flurry plays Vader from hand with Ambush + prevent-2. Vader's When
# Played shields a friendly (himself) and an enemy (SOR_046); his Ambush attack's On Attack then defeats
# the shielded SOR_046. Vader ends in play with his own Shield and Ambush.

## GIVEN
CommonSetup: ybk/ggw/{myResources:7;handCardIds:LOF_220,LOF_037}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_037
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:HASKEYWORD:Ambush
