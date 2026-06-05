# SHD_037 Supreme Leader Snoke — passive field-presence debuff:
#   "Each enemy non-leader unit gets –2/–2."
# P1 plays Snoke. P2's AT-AT (9/9) is an enemy unit → 7/7. P1's own Imperial
# Dark Trooper (3/3) is friendly to Snoke's controller → unaffected, stays 3/3.

## GIVEN
CommonSetup: bbk/bbk/{myResources:8;handCardIds:SHD_037}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:1:CARDID:SHD_037
P2GROUNDARENAUNIT:0:CARDID:SOR_088
P2GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENAUNIT:0:HP:7
