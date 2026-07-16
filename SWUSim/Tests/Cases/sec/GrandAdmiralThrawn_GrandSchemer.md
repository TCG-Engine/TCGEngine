# OppChooses_Captures
#// SEC_193 Grand Admiral Thrawn (Ground, 8/7, Cunning/Villainy, cost 7) — When Played: an opponent may
#//   choose a non-leader unit they control; if they do, Thrawn captures it. P2 picks SOR_046 → captured.

## GIVEN
CommonSetup: yyk/grw/{myResources:7}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_193

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_193
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# OppDeclines_ReadiesThrawn
#// SEC_193 Grand Admiral Thrawn — if the opponent declines, ready Thrawn (he enters exhausted, then readies).

## GIVEN
CommonSetup: yyk/grw/{myResources:7}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_193

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_193
P1GROUNDARENAUNIT:0:READY
P1NODECISION

---

# WhenPlayed_CaptureTokenDefeatsInstead
#// SEC_193 Grand Admiral Thrawn — When Played: an opponent may choose a non-leader unit they control for
#// Thrawn to capture. Tokens CANNOT be captured — a token that would be captured is defeated and removed
#// from play instead (never becomes a captive). P2 offers up their SEC_T01 Spy token; it must be defeated
#// (→ P2 discard), NOT attached as a captive under Thrawn.
## GIVEN
CommonSetup: yyk/yyk/{myResources:7}
WithP1Hand: SEC_193
WithP2GroundArena: SEC_T01:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_193
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1NODECISION
P2NODECISION
