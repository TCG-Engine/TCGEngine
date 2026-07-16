# DealsFiveIndirectToChosenPlayer
#// JTL_234 Torpedo Barrage (event) — "Deal 5 indirect damage to a player." P1 plays it and directs the
#// 5 indirect at the opponent; P2 (the damaged player) assigns the 5 unpreventable damage among their own
#// base and units — 4 to their base, 1 to their Lurking TIE Phantom (SHD_187). Because indirect damage is
#// assigned by the damaged player to their OWN cards, SHD_187's "can't be damaged by enemy card abilities"
#// does not apply — it legally takes 1.

## GIVEN
CommonSetup: yyk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: JTL_234
WithP2SpaceArena: SHD_187:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myBase-0:4,mySpaceArena-0:1

## EXPECT
P2BASEDMG:4
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2SPACEARENAUNIT:0:DAMAGE:1
P1NODECISION