# SHD_187 Lurking TIE Phantom (3-cost space) — Raid 2 + "This unit can't be captured, damaged, or defeated
# by enemy card abilities." Guard: P2's Daring Raid (deal 2 to a unit) targeting the Phantom is prevented.

## GIVEN
CommonSetup: yyk/rrk/{theirResources:1}
WithActivePlayer: 2
WithP1SpaceArena: SHD_187:1:0
WithP2Hand: SHD_178

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SHD_187
P1SPACEARENAUNIT:0:DAMAGE:0
