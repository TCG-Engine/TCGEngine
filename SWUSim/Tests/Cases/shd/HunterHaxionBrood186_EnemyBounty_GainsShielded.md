# SHD_186 Hunter of the Haxion Brood (3-cost, Cunning/Villainy) — "While an enemy unit has a Bounty, this
# unit gains Shielded." Guard: with the enemy Bounty unit SHD_095 in play it has Shielded; the negative case
# is covered by the sibling test.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SHD_186:1:0
WithP2GroundArena: SHD_095:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_186
P1GROUNDARENAUNIT:0:HASKEYWORD:Shielded
