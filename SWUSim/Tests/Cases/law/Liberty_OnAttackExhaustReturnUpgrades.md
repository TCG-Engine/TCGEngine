# LAW_224 Liberty (9/7, space, Sentinel) — When Played/On Attack: exhaust an enemy unit and return all
# upgrades on it that cost 4 or less to their owners' hands. Attacks the base; exhaust SEC_080 and
# return SOR_120 (cost 2) to P2's hand.

## GIVEN
CommonSetup: yyw/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_224:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1
