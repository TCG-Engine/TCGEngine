# LAW_206 That's a Rock (Aggression event, cost 1) — "Deal 1 damage to a unit." Single unit on board
# (enemy SOR_046) -> auto-target -> 1 damage.

## GIVEN
CommonSetup: rrk/bgw/{myResources:1}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_206

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:1
