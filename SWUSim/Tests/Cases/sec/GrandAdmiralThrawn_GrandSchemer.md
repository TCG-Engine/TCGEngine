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
#// from play instead (never becomes a captive). P2 offers up their SEC_T01 Spy token; it must be defeated,
#// NOT attached as a captive under Thrawn. A defeated TOKEN CEASES to exist rather than entering a discard
#// pile, so P2's discard stays EMPTY.
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
P2DISCARDCOUNT:0
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

---

# WhenPlayed_CapturesAUnitThatChangedControl
#// SEC_193 Grand Admiral Thrawn — "an opponent may choose a non-leader unit THEY CONTROL" is read at
#// resolution, so a unit the opponent only just took control of is a legal choice. P2 steals P1's
#// SOR_095 with SOR_122 Traitorous first; P1 then plays Thrawn and P2 offers up that stolen unit, which
#// Thrawn captures.

## GIVEN
CommonSetup: yyk/ggw
WithActivePlayer: 2
WithP1Resources: 7
WithP2Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP1Hand: SEC_193
WithP2Hand: SOR_122

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_193
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# WhenDefeated_UnderEnemyControl_TheNewControllerCaptures
#// SEC_193 Grand Admiral Thrawn — the When Defeated ("a FRIENDLY unit captures an ENEMY non-leader unit
#// in the same arena") is resolved by whoever controls Thrawn when he dies. P2 plays JTL_043 No Glory,
#// Only Results on him: control moves to P2 first, so P2's own unit does the capturing and P1's unit is
#// the one captured.

## GIVEN
CommonSetup: yyk/bbk
WithActivePlayer: 2
WithP2Resources: 6
WithP1GroundArena: SEC_193:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Hand: JTL_043

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# TwinSuns_TheUNITLESSSeatStaysInThePicker
#// ⚠ THE ELIGIBILITY CELL — added 2026-08-24. Asserts the MENU (an outcome-only section cannot pin
#// eligibility; the harness does not validate OPTIONCHOOSE candidates).
#//
#// ⚠⚠ SEC_193 IS WHERE THE SWEEP'S CANONICAL I2 SENTENCE STOPS BEING A SAFE SHORTCUT. The plan names
#// "an opponent chooses a unit they control" as the textbook case for filtering to opponents WITH a unit,
#// and SEC_193 matches that wording WORD FOR WORD — but the filter is wrong here.
#// Cad Bane's other leg deals 1 damage, so naming a unit-less opponent achieves NOTHING and filtering is
#// pure I2. SEC_193's other leg is "IF THEY DON'T, READY THIS UNIT" — a real, positive outcome for the
#// caster. Naming a unit-less opponent is a GUARANTEED ready of an 8/7; naming a stocked one is only a
#// MAYBE-capture they can decline into that same ready. Materially different plays, so the menu entry is
#// NOT a choice among nothing.
#// The gate is board-level instead: if NO opponent anywhere holds a non-leader unit, every answer
#// collapses to "ready Thrawn" — genuinely degenerate — so the picker is skipped and he just readies.
#//
#// Seats 2 and 3 hold a non-leader unit; SEAT 4 HOLDS NOTHING and must still be offered.
#// Mutation check: filter $eligible to opponents with a unit and P1OPTIONHAS:P4 reds.

## GIVEN
CommonSetup: yyk/grw/{myResources:7}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: SEC_193
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_046:1:0
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1OPTIONHAS:P2
P1OPTIONHAS:P3
P1OPTIONHAS:P4
P1OPTIONNOT:P1
