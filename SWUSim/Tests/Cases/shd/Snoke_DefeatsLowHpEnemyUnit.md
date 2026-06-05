# SHD_037 Supreme Leader Snoke — the passive –2/–2 lowers HP directly (not damage),
# so an enemy non-leader unit whose HP drops to 0 is defeated as a state-based effect.
# P1 plays Snoke while P2 controls Leia (SOR_189, 2/2) → 2/2 becomes 0/0 → defeated.

## GIVEN
CommonSetup: bbk/bbk/{myResources:8;handCardIds:SHD_037}
WithP2GroundArena: SOR_189:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_037
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
