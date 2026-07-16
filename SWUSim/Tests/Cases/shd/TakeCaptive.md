# SOR015_ReadiesResourceOnCaptureLeavePlay
#// SOR_015 Boba Fett (leader, undeployed, ready) — "When an enemy unit leaves play: You may exhaust
#// this leader. If you do, ready a resource." Capture counts as leaving play (CR 8.34).
#// P1 plays SHD_131 Take Captive: P1's SOR_095 captures P2's SOR_128. The captured unit leaving play
#// triggers Boba's always-yes reaction → Boba auto-exhausts and readies the one exhausted resource.
#// Resources: 3 ready + 1 exhausted. After paying cost 3: 0 ready from the main pool.
#// Boba fires on capture: readies the 1 exhausted resource → P1RESAVAILABLE becomes 1.
#// Both SHD_131 choices auto-picked (single eligible unit each step).
#// Assertions: capture happened; Boba is now EXHAUSTED; P1 has 1 ready resource.
#// Base: SOR_024 Echo Base (Command aspect) covers SHD_131's Command aspect requirement.

## GIVEN
CommonSetup: gyk/brw/{
  myLeader:SOR_015;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_131
WithP1Resources: 3:SOR_128:1,1:SOR_128:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_128
P1LEADER:EXHAUSTED
P1RESAVAILABLE:1

---

# RescueOnCaptorDefeat
#// Capture rescue (CR 8.34.4): when the captor leaves play, all captives return to their owner's
#// arena EXHAUSTED (no WhenPlayed triggers).
#// Setup: P1 has SOR_095 (Battlefield Marine, 3/3) as the captor.
#//        P2 has SOR_128 (Death Star Stormtrooper, 1/3) to be captured (index 0)
#//             + LAW_124 (Industrious Team, 4/7) at index 1 — will defeat the captor via combat.
#// Step 1: P1 plays SHD_131 Take Captive.
#//   - Capturer: auto-picked (SOR_095, only P1 ground unit).
#//   - Captive:  two P2 ground units → player picks SOR_128 (theirGroundArena-0).
#//   - SOR_128 leaves P2's arena; becomes captive subcard on SOR_095.
#// Step 2: playing the event passed the turn to P2 → P2's LAW_124 (now index 0) attacks P1's SOR_095 (captor).
#//   - LAW_124 deals 4 damage to SOR_095 (3HP) → SOR_095 defeated.
#//   - SWURescueCaptivesOf fires: SOR_128 returned to P2's ground arena EXHAUSTED (Status:0).
#// Final: P1 has 0 ground units; P2 has LAW_124 (index 0, ready after attack is exhausted) +
#//        rescued SOR_128 (index 1, exhausted per CR 8.34.4).
#// Resources: 3 ready → 0 after paying SHD_131. (LAW_124 costs nothing to play; already in arena.)
#// Leader: ggk (Tarkin, Command+Villainy) + Echo Base (Command covered; no penalty).

## GIVEN
CommonSetup: ggk/grw/{myResources:3;handCardIds:SHD_131}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:1:CARDID:SOR_128
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# BountyCollectedOnCapture
#// SHD_131 Take Captive — Bounty is collected on capture (CR 7.6.3).
#// P2's unit is SHD_027 Hylobon Enforcer (Bounty: Draw a card).
#// P1 captures SHD_027 via SHD_131; CollectCaptureTriggers offers Bounty to P1 (the capturing player).
#// P1 answers YES to collect → draws 1 card from deck (SOR_095 placed in P1's deck).
#// P1 started with 0 cards in hand after playing SHD_131 (event goes to discard) + 1 drawn from deck.
#// Both capturer (P1's SOR_095) and captive (P2's SHD_027) are auto-picked (single eligible each step).
#// Assertions: capture happened (SHD_027 subcard on captor); P1 drew 1 card (P1HANDCOUNT:1).
#// Resources: 3 ready → 0 after paying cost 3.
#// Leader: ggk (Tarkin, Command+Villainy) + Echo Base (Command aspect covered).

## GIVEN
CommonSetup: ggk/grw/{myResources:3;handCardIds:SHD_131}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_027:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SHD_027
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:0
P1HANDCOUNT:1

---

# CapturesNoBounty_Ground
#// SHD_131 Take Captive — basic ground capture, no bounty.
#// P1 plays Take Captive (Command/Tactic, cost 3).
#// P1 has one friendly ground unit (SOR_095 Battlefield Marine, 3/3).
#// P2 has one enemy ground unit (SOR_128 Death Star Stormtrooper, 1/3) — no Bounty keyword.
#// Both choices are auto-picked (SWUQueueChooseTarget PASSPARAMETER: single eligible target each step).
#// After capture: SOR_128 leaves P2's arena; becomes IsCaptive subcard on SOR_095.
#// Assertions: P2 ground arena empty; P1 unit has UPGRADECOUNT 1; captive CardID = SOR_128.
#// Resources: 3 ready — exact cost of SHD_131 → 0 remaining.
#// Leader: ggk (Tarkin, Command+Villainy) + Echo Base (Command aspect covered; no penalty).

## GIVEN
CommonSetup: ggk/grw/{myResources:3;handCardIds:SHD_131}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_128
P2GROUNDARENACOUNT:0
P1RESAVAILABLE:0

---

# SpaceArena_SameArenaScoping
#// SHD_131 Take Captive — same-arena scoping: space capturer captures space enemy only.
#// P1 has one space unit (SOR_162 Disabling Fang Fighter, 3/3) as the capturer.
#// P2 has one space unit (SOR_237 Alliance X-Wing, 2/3) as the enemy to capture.
#// No ground units on either side, confirming space→space arena derivation in SHD131_PICK_CAPTURER.
#// Both choices auto-picked (single eligible each step via PASSPARAMETER).
#// Assertions: P1 space unit has UPGRADECOUNT 1; captive is SOR_237; P2 space arena empty.
#// Resources: 3 ready → 0 remaining after paying SHD_131 cost 3.
#// Leader: ggk (Tarkin, Command+Villainy) + Echo Base (Command aspect covered).

## GIVEN
CommonSetup: ggk/grw/{myResources:3;handCardIds:SHD_131}
P1OnlyActions: true
WithP1SpaceArena: SOR_162:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_162
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_237
P2SPACEARENACOUNT:0
P1RESAVAILABLE:0
