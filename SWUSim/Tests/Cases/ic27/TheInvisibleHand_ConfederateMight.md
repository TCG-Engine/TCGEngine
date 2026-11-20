# WhenPlayed_NextCardCostsNineLess
#// IC27_149 The Invisible Hand (Space 9/9, cost 10, Cunning/Villainy) — "When Played/When Defeated: The next
#// card you play this phase costs [9 resources] less."
#// Under a Cunning/Villainy leader + Cunning base the Hand plays at its printed 10. IC27_121 Kanan Jarrus is a
#// vanilla 6-cost Aggression/Heroism unit: +2 per off-aspect pip = an effective 10. 11 resources: the Hand
#// leaves 1, and Kanan at 10 - 9 = 1 takes the last one. Without the discount Kanan stays in hand.
#// COVERAGE: offer=N/A (no choice anywhere on the card: both windows arm a charge, nothing is targeted) ·
#//           decline=N/A (no "may": both windows are mandatory) ·
#//           boundary=the amount 9: this section (1 ready pays a 10 → 9 less, not 8) and DiscountIsNineNotTen
#//           (0 ready does NOT pay a 10 → 9 less, not 10) ·
#//           control=ControlChange_TheNewControllerGetsTheWhenDefeatedCharge ·
#//           reqboundary=WhenPlayed_AcrossARequestBoundary ·
#//           duration=ExpiresAtTheEndOfThePhase + DefeatedAtRegroupBySneakAttack_ChargeDoesNotReachTheNextActionPhase +
#//           OnlyTheNextCard_EvenACheapOne ·
#//           card type=OnlyTheNextCard_EvenACheapOne (event) · NextUpgradeIsDiscounted · NextPilotIsDiscounted
#//           modes=2P only ("you" = the ability's controller in every format; no player reference, no friendly/enemy)

## GIVEN
CommonSetup: yyk/bbw/{myResources:11}
P1OnlyActions: true
WithP1Hand: [IC27_149 IC27_121]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:IC27_149
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_121
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1NOGLOBALEFFECT:SWU_IC27149_DISCOUNT_NEXT

---

# DiscountIsNineNotTen
#// Same board with 10 resources: the Hand leaves 0, and Kanan at 10 - 9 = 1 cannot be paid. A 10-less
#// discount would play him for free. The refused play spends nothing, so the charge is still armed.

## GIVEN
CommonSetup: yyk/bbw/{myResources:10}
P1OnlyActions: true
WithP1Hand: [IC27_149 IC27_121]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:0
P1GLOBALEFFECT:SWU_IC27149_DISCOUNT_NEXT

---

# WhenPlayed_AcrossARequestBoundary
#// The charge is armed in one request and read in the next: it must survive the gamestate round-trip.

## GIVEN
CommonSetup: yyk/bbw/{myResources:11}
P1OnlyActions: true
WithP1Hand: [IC27_149 IC27_121]

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_121
P1RESAVAILABLE:0

---

# OnlyTheNextCard_EvenACheapOne
#// "The next CARD" is any type, and the charge is spent on it even when it costs less than 9. 10 resources:
#// the Hand leaves 0, SOR_251 Confiscate (a neutral 1-cost event; no upgrades in play, so it fizzles cleanly)
#// is the next card and plays for free, then Kanan (10) pays full and stays in hand.

## GIVEN
CommonSetup: yyk/bbw/{myResources:10}
P1OnlyActions: true
WithP1Hand: [IC27_149 SOR_251 IC27_121]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_251
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:0
P1NOGLOBALEFFECT:SWU_IC27149_DISCOUNT_NEXT

---

# NextUpgradeIsDiscounted
#// An upgrade is a card too. SOR_120 Academy Training (Command, 2 + 2 off-aspect = 4) attaches to the Hand —
#// the only unit in play — with 0 ready. An upgrade computes its cost after the host is picked, AFTER the
#// play commits, so this is the path where a charge spent at commit would make it pay full.

## GIVEN
CommonSetup: yyk/bbw/{myResources:10}
P1OnlyActions: true
WithP1Hand: [IC27_149 SOR_120]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_120
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1NOGLOBALEFFECT:SWU_IC27149_DISCOUNT_NEXT

---

# NextPilotIsDiscounted
#// A Piloting unit played AS A PILOT is still the next card. JTL_211 Independent Smuggler (Piloting
#// [1 resource Cunning]) goes onto the Hand — a Vehicle — with 0 ready.

## GIVEN
CommonSetup: yyk/bbw/{myResources:10}
P1OnlyActions: true
WithP1Hand: [IC27_149 JTL_211]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_211
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1NOGLOBALEFFECT:SWU_IC27149_DISCOUNT_NEXT

---

# ExpiresAtTheEndOfThePhase
#// "This phase": an unspent charge does not reach the next action phase. The Hand leaves 1 of 11; P1 plays
#// nothing else and the round ends. Next round all 11 are ready, and Kanan pays his full 10 — a leaked charge
#// would leave 10.

## GIVEN
CommonSetup: yyk/bbw/{myResources:11}
P1OnlyActions: true
WithP1Hand: [IC27_149 IC27_121]
WithP1Deck: [SOR_225 SOR_225 SOR_225 SOR_225]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_121
P1RESAVAILABLE:1
P1NOGLOBALEFFECT:SWU_IC27149_DISCOUNT_NEXT

---

# WhenDefeated_ArmsTheCharge
#// The Hand (8 damage on 9 HP) attacks a TIE/ln Fighter (2/1): both die. Its When Defeated arms the charge,
#// and Kanan (10) is played with the 1 ready resource.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
P1OnlyActions: true
WithP1SpaceArena: IC27_149:1:8
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: IC27_121

## WHEN
- P1>AttackSpaceArena:0:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:0
P1DISCARDUNIT:0:CARDID:IC27_149
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_121
P1RESAVAILABLE:0

---

# WhenDefeated_SurvivingTheAttackArmsNothing
#// Negative: the same attack from an undamaged Hand. It survives, no window fires, and Kanan stays in hand.

## GIVEN
CommonSetup: yyk/bbw/{myResources:1}
P1OnlyActions: true
WithP1SpaceArena: IC27_149:1:0
WithP2SpaceArena: SOR_225:1:0
WithP1Hand: IC27_121

## WHEN
- P1>AttackSpaceArena:0:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:1
P2SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1HANDCOUNT:1
P1RESAVAILABLE:1
P1NOGLOBALEFFECT:SWU_IC27149_DISCOUNT_NEXT

---

# WhenPlayedAndWhenDefeated_StackOnTheNextCard
#// Both windows in one phase: P1 plays the Hand (charge 1), P2 Vanquishes it (charge 2). Both describe "the
#// next card you play this phase", so the next card gets both: Kanan (10) is free with 0 ready. A single
#// charge would leave him at 1 and in hand. Both charges are spent.

## GIVEN
CommonSetup: yyk/bbw/{myResources:10;theirResources:5}
WithActivePlayer: 1
WithP1Hand: [IC27_149 IC27_121]
WithP2Hand: SOR_078

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>Drain
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_121
P1RESAVAILABLE:0
P1NOGLOBALEFFECT:SWU_IC27149_DISCOUNT_NEXT

---

# OpponentsPlay_NeitherUsesNorSpendsTheCharge
#// The charge is the controller's. After P1 plays the Hand, P2 plays SOR_095 Battlefield Marine at its full
#// 4 (Command off-aspect under a Vigilance base); P1's charge is still there for Kanan.

## GIVEN
CommonSetup: yyk/bbw/{myResources:11;theirResources:4}
WithActivePlayer: 1
WithP1Hand: [IC27_149 IC27_121]
WithP2Hand: SOR_095

## WHEN
- P1>PlayHand:0
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_121
P1RESAVAILABLE:0
TURNPLAYER:2

---

# ControlChange_TheNewControllerGetsTheWhenDefeatedCharge
#// JTL_043 No Glory, Only Results takes control of P2's Hand, then defeats it: P1 controls it when it is
#// defeated, so P1 resolves the When Defeated and P1's next card is discounted. The Hand goes to its OWNER's
#// discard. NGOR is 5 + 2 (Vigilance off-aspect) = 7 of 8, leaving 1 for Kanan at 10 - 9.

## GIVEN
CommonSetup: yyk/bbw/{myResources:8}
P1OnlyActions: true
WithP2SpaceArena: IC27_149:1:0
WithP1Hand: [JTL_043 IC27_121]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P2SPACEARENACOUNT:0
P2DISCARDUNIT:0:CARDID:IC27_149
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_121
P1RESAVAILABLE:0
P2NOGLOBALEFFECT:SWU_IC27149_DISCOUNT_NEXT

---

# DefeatedAtRegroupBySneakAttack_ChargeDoesNotReachTheNextActionPhase
#// SOR_219 Sneak Attack plays the Hand (10 - 3 = 7) and defeats it at the start of the regroup phase. Its When
#// Defeated resolves in the REGROUP phase, where no card can be played, so "this phase" is over before it can
#// be used. Next round all 11 resources are ready and Kanan pays his full 10 — a leaked charge would leave 10.

## GIVEN
CommonSetup: yyk/bbw/{myResources:11}
P1OnlyActions: true
WithP1Hand: [SOR_219 IC27_149 IC27_121]
WithP1Deck: [SOR_225 SOR_225 SOR_225 SOR_225]
WithP2Deck: [SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_121
P1RESAVAILABLE:1
P1NOGLOBALEFFECT:SWU_IC27149_DISCOUNT_NEXT
