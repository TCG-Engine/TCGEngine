# Support (ASH) — choosing among multiple eligible attackers. ASH_154 (Raid 1 + Support) is played with
# two ready Marines eligible. The player picks index 1; that Marine gains Raid 1 and attacks the base for
# 3 + 1 = 4. The non-chosen Marine (index 0) stays ready.

## GIVEN
CommonSetup: yrw/grw/{myResources:9;handCardIds:ASH_154}
WithP1GroundArena: SOR_095:1:0   # Marine A (index 0)
WithP1GroundArena: SOR_095:1:0   # Marine B (index 1)

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:READY
P1GROUNDARENAUNIT:1:EXHAUSTED
