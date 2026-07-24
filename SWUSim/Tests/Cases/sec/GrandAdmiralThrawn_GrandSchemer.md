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

---

# WhenPlayed_OppPicksSpaceUnit_Captured
#// SEC_193 Grand Admiral Thrawn (Ground) — When Played the opponent may choose a non-leader unit in EITHER
#// arena to be captured. P2 has a space unit (SOR_086 Gladiator Star Destroyer) and a ground unit (SOR_232
#// AT-ST). P2 offers the Gladiator → Thrawn (ground) captures it as a facedown captive.

## GIVEN
CommonSetup: yyk/grw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SEC_193
WithP2SpaceArena: SOR_086:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:mySpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_193
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# WhenDefeated_FriendlyGroundCapturesEnemyGround
#// SEC_193 Grand Admiral Thrawn — When Defeated: a friendly unit captures an enemy non-leader unit in the
#// SAME arena. Thrawn is at 6 damage (1 HP). P1 Open-Fires (SOR_172) its own Thrawn → he is defeated → the
#// When Defeated fires: P1 picks the friendly GROUND unit SOR_095, which then captures the enemy ground
#// SOR_232 (the friendly space unit SOR_132 is also an eligible captor, so the captor choice is real).

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP1GroundArena: SEC_193:1:6
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_132:1:0
WithP2GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1NODECISION

---

# WhenDefeated_FriendlySpaceCapturesEnemySpace
#// SEC_193 Grand Admiral Thrawn — When Defeated: the captor and captive must share an arena. P1 picks the
#// friendly SPACE unit SOR_132 (Imperial Interceptor), which captures the enemy space SOR_086 Gladiator
#// Star Destroyer. (Same defeat setup as above via Open Fire on the 1-HP Thrawn.)

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SOR_172
WithP1GroundArena: SEC_193:1:6
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_132:1:0
WithP2SpaceArena: SOR_086:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_132
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P2SPACEARENACOUNT:0
P1NODECISION

---

# WhenPlayed_NoEnemyUnits_ReadiesThrawn
#// SEC_193 Grand Admiral Thrawn — When Played, if the opponent controls no non-leader units there is nobody
#// to offer for capture, so Thrawn (who enters exhausted) is readied instead. No decision is queued to
#// either player.

## GIVEN
CommonSetup: yyk/grw/{myResources:7}
P1OnlyActions: true
WithP1Hand: SEC_193

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SEC_193
P1GROUNDARENAUNIT:0:READY
P1NODECISION
P2NODECISION
