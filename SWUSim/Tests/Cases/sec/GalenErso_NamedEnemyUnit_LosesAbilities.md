# SEC_046 Galen Erso (Unit, 3/5, cost 4, Vigilance/Heroism, Imperial, Plot)
#   "When Played: Name a card. While this unit is in play, each non-leader card an opponent owns with
#    that name, including those not in play, loses all abilities (and can't gain abilities)."
# P1 plays Galen and names "Cloud City Wing Guard" (SOR_063, an enemy Sentinel unit). While Galen is in
# play, P2's SOR_063 loses all abilities, so it no longer has Sentinel. A second enemy Sentinel unit
# (SOR_037, a DIFFERENT name) is NOT named, so it KEEPS Sentinel — proving the name-match gate.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SEC_046
WithP2GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_037:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Cloud City Wing Guard

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_046
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
