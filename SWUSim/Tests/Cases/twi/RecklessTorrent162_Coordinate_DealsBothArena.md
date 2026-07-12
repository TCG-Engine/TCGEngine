# TWI_162 Reckless Torrent (Unit 3/1, Space, cost 3) — "Coordinate - When Played: You may deal 2
# damage to a friendly unit and 2 damage to an enemy unit in the same arena." Played with a friendly
# SOR_046 + a Clone token in the ground arena (3 units incl. the frigate → Coordinate active) and an
# enemy SEC_080 in ground. Pick SOR_046 (friendly) → it takes 2, then the only enemy SEC_080 takes 2.

## GIVEN
CommonSetup: rrk/grw/{myResources:3;handCardIds:TWI_162}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: TWI_T02:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:TWI_162
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:2
