# SEC_016 Padmé Amidala (deployed) — "When you reveal or discard 1 or more cards from your hand: You may
# deal 1 damage to a unit." P1 plays SEC_062 (which discloses = reveals a card from hand) → the deployed
# Padmé reacts and deals 1 to the enemy SOR_095. (SEC_062's own draw also resolves.)

## GIVEN
P1LeaderBase: SEC_016:1:1:1/JTL_019
P2LeaderBase: SOR_002/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_062
WithP1Hand: SEC_059
WithP1Deck: [SOR_095 SOR_095]
WithP1GroundArena: SEC_016:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:CARDID:SEC_062
