# WhenPlayed_CaptureBudget
#// TWI_187 Cad Bane (Unit 7/7, Ground, cost 7, Cunning/Villainy, Underworld/Bounty Hunter) — "When Played:
#// This unit captures up to 3 enemy non-leader units with a total of 8 or less remaining HP." Capturing
#// SOR_046 (7 remaining HP) leaves budget 1, so only a 1-HP SOR_128 can be captured next; the second
#// SOR_128 exceeds the exhausted budget and stays. Cad Bane ends with 2 captives (subcards). Base y +
#// leader yk cover both Cunning/Villainy pips.

## GIVEN
CommonSetup: yyk/bbw/{myResources:7;handCardIds:TWI_187}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_187
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128

---

# WhenPlayed_Decline_CaptureOne
#// TWI_187 Cad Bane — the capture loop is optional at each step: capturing one SOR_128 then declining
#// leaves Cad Bane with a single captive and the rest of the enemy board intact.

## GIVEN
CommonSetup: yyk/bbw/{myResources:7;handCardIds:TWI_187}
P1OnlyActions: true
WithP2GroundArena: [SOR_046:1:0 SOR_128:1:0 SOR_128:1:0]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENACOUNT:2

---

# TwinSuns_RescueIsOfferedToTheACTUALDefendingPlayer
#// "On Attack: THE DEFENDING PLAYER may rescue a card they own guarded by this unit. If they do, draw 2."
#// The handler computed $opp = OtherPlayer($player) and then required a captive whose Owner === $opp.
#// At four seats that is seat 2 — so attacking seat 4 while holding SEAT 4's captive matched nothing,
#// $hasCaptive stayed false, and the ability produced NO PROMPT AT ALL. Not merely the wrong player
#// asked: the defender was silently denied the rescue the card promises them.
#//
#// Cad Bane guards one captive OWNED BY SEAT 4 (the new explicit-owner form of the captive directive —
#// the implicit owner is seat 2, which is exactly the case that hid this). Attacking seat 4 must put the
#// rescue YESNO on seat 4.

## GIVEN
CommonSetup: yyk/bbw/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: TWI_187:1:0
WithP1GroundArenaCaptive: 0:SOR_128:4

## WHEN
- P1>AttackGroundArena:0:P4B

## EXPECT
SEATCOUNT:4
P4HASDECISION
P2NODECISION
