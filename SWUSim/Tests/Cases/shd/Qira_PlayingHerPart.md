# NamedCardCostsThreeMore
#// SHD_202 Qi'ra (Unit, When Played) — "Look at an opponent's hand, then name a card. While this unit is in
#// play, each card with that name costs 3 resources more for your opponents to play." P1 plays Qi'ra and
#// names P2's SOR_063 (mono-Vigilance, cost 3). On P2's turn, SOR_063 now costs 3 + 3 = 6, so P2 (with
#// exactly 6 resources) spends all of them to play it (only 3 without the surcharge).

## GIVEN
CommonSetup: yyk/bbk
WithActivePlayer: 1
WithP1Resources: 8
WithP1Hand: SHD_202
WithP2Resources: 6
WithP2Hand: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P2RESAVAILABLE:0

---

# ControlChange_SurchargeFollowsThePlayerWhoPlayedHer
#// SHD_202 Qi'ra — official ruling: "Until Qi'ra leaves play, your opponent's cards cost more. This
#// effect is NOT changed if an opponent takes control of Qi'ra." P1 plays Qi'ra and names P2's SOR_063
#// (Cloud City Wing Guard). P2 then plays Change of Heart (SOR_224, cost 6 Cunning) taking control of
#// Qi'ra — she moves to P2's ground arena. The surcharge follows the player who PLAYED her (P1), so P2
#// STILL pays 3 + 3 = 6 for SOR_063 even though P2 now controls Qi'ra: P2 spends 6 (Change of Heart) +
#// 6 (surcharged SOR_063) = 12, leaving 0. Regression guard: the surcharge was previously keyed to
#// Qi'ra's CURRENT controller, so a control change dropped it (P2 would have paid only 3, leaving 3).
#// Aspects: P1 yyw covers Qi'ra (Cunning/Heroism); P2 byk covers Change of Heart (Cunning) + SOR_063 (Vigilance).

## GIVEN
CommonSetup: yyw/byk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:6;
  theirResources:12
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: SHD_202
WithP2Hand: [SOR_224 SOR_063]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-1
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>Pass
- P2>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SHD_202
P2GROUNDARENAUNIT:1:CARDID:SOR_063
P2RESAVAILABLE:0

---

# NameExcludesSubtitle_OtherPrintingAlsoSurcharged
#// SHD_202 Qi'ra — official ruling: "Abilities that refer to a card's 'name' do not include the subtitle."
#// So "each card with that name" matches by NAME, hitting every printing that shares it. P1 plays Qi'ra
#// and names P2's SOR_229 (Cell Block Guard). P2 then plays a DIFFERENT printing of the same card —
#// SHD_238 (also "Cell Block Guard", different CardID) — which is still surcharged: cost 3 + 3 = 6, so
#// P2 (with 6 resources) spends all of them. Regression guard: the surcharge was previously keyed to the
#// named CardID, so the other printing (SHD_238) escaped it and would have cost only 3.

## GIVEN
CommonSetup: yyw/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:6;
  theirResources:6
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Hand: SHD_202
WithP2Hand: [SOR_229 SHD_238]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirHand-0
- P2>PlayHand:1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SHD_238
P2RESAVAILABLE:0

---

# SurchargeAppliesToSmuggledCard
#// SHD_202 Qi'ra — the +3 "costs more for your opponents" surcharge must ALSO apply to a card the opponent
#// plays via SMUGGLE (regression: the Smuggle payment path built cost from GetEffectiveSmuggleCost and
#// bypassed SWUComputePlayCost, so it dodged the surcharge entirely). P2 controls Qi'ra having named SHD_111
#// Collections Starhopper (Smuggle [3 Command]; ggw base covers Command → bracket cost 3). With the +3
#// surcharge the smuggle costs 6, so P1 with exactly 6 ready resources (the SHD_111 resource + 5 fillers)
#// can pay it and SHD_111 enters the space arena. Paired with the one-below negative below to pin cost == 6.

## GIVEN
CommonSetup: ggw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SHD_202:1:0
WithP2GlobalEffect: SWU_SHD202_NAMED|SHD_111
WithP1Resources: 1:SHD_111:1,5:SOR_251:1

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_111

---

# SurchargeAppliesToSmuggledCard_RejectedOneBelow
#// Same setup as SurchargeAppliesToSmuggledCard but P1 has only 5 ready resources. With the Qi'ra +3
#// surcharge the smuggle costs 6 > 5, so it is REJECTED and SHD_111 stays in resources (space arena empty).
#// Without the surcharge the bracket cost is only 3 ≤ 5 and it would play — so an empty arena here proves
#// the surcharge raised the cost to 6.

## GIVEN
CommonSetup: ggw/bbk/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SHD_202:1:0
WithP2GlobalEffect: SWU_SHD202_NAMED|SHD_111
WithP1Resources: 1:SHD_111:1,4:SOR_251:1

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1SPACEARENACOUNT:0
