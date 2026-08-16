# Bounty_ExhaustUnit
#// SHD_211 Fugitive Wookiee — Unit, cost 2, 3/3, Ground, [Cunning], trait Wookiee.
#// "Bounty - Exhaust a unit." (Bounty = when this unit is DEFEATED **or CAPTURED**, its opponent collects.)
#// COVERAGE: offer=Bounty_ExhaustUnit + Bounty_CaptureAlsoCollects — both leave two live units on the
#//           board so the exhaust pick is a real MZCHOOSE rather than an auto-resolve, and the second
#//           shows the pool spans BOTH sides (P1 picks an enemy unit) ·
#//           request boundary=Bounty_CaptureAlsoCollects — the bounty is collected after the capture has
#//           already moved SHD_211 out of the arena, so the pool is read from the post-capture board ·
#//           control=Bounty_CaptureAlsoCollects — the collector (P1) is not the host's controller (P2)
#//           and exhausts a unit on the OPPONENT's side ·
#//           boundary pair=Bounty_ExhaustUnit (defeat trigger) + Bounty_CaptureAlsoCollects (capture
#//           trigger) — the two halves of "defeated or captured" ·
#//           decline=N/A (the collect prompt's only branches are collect / do-nothing; declining leaves
#//           the board identical to no bounty at all).
#// LAW_124 defeats it; P1 collects. Two units remain (the exhausted attacker + a ready marine) → real
#// MZCHOOSE; P1 picks the ready marine, which exhausts.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_211:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Bounty_CaptureAlsoCollects
#// SHD_211 Fugitive Wookiee — the bounty fires on CAPTURE as well as on defeat. P1 plays SHD_131 Take
#// Captive (Command, cost 3; the g base covers it): P1's SOR_095 is the only legal captor and SHD_211 the
#// only legal captive, so both steps auto-resolve. SHD_211 leaves the arena as a facedown card under
#// SOR_095 — never defeated — and the bounty still goes to its opponent, P1, who exhausts the enemy
#// SOR_225 out of a two-unit pool (its own ready SOR_095 is the other option).

## GIVEN
CommonSetup: grw/grw/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_131
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_211:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_211
P2SPACEARENAUNIT:0:EXHAUSTED
