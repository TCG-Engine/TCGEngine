# SHD_123 Bounty Hunter's Quarry (upgrade) — "Attached unit gains: 'Bounty - Search the top 5 cards of
# your deck...'." The attached unit gains the Bounty keyword (the badge shows). Here the enemy
# Battlefield Marine wears SHD_123 and reads as a Bounty unit; a plain marine does not.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_123
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:HASKEYWORD:Bounty
P2GROUNDARENAUNIT:1:NOTKEYWORD:Bounty
