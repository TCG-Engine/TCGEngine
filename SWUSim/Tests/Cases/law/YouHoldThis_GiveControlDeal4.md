# LAW_085 You Hold This (Aggression,Cunning event, cost 1) — "Choose a friendly non-leader unit. An
# opponent takes control of it. If they do, deal 4 damage to another unit in the same arena." P1 gives
# away SEC_080; the only other ground unit (P2's SOR_046, 3/7) takes 4 and survives.

## GIVEN
CommonSetup: ryk/bgw/{myResources:1}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_085

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P1DISCARDCOUNT:1
