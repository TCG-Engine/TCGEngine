# Deploy_NextPlotCosts3Less
#// SEC_001 Chancellor Palpatine (deployed) — When Deployed: The next card you play using Plot this
#// phase costs 3 resources less. P1 controls SEC_034 Cad Bane (Plot, cost 5, Vigilance/Villainy) as
#// myResources-0 + 6 vanilla (7 ready — meets SEC_001's deploy threshold of 7). SEC_001's V/V leader
#// aspects cover SEC_034's V/V pips → no penalty. Deploy arms the −3, then the Plot window plays
#// SEC_034 for 5 − 3 = 2 (7 ready → 5). No enemy units → Cad Bane's When Played fizzles cleanly.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SEC_034:1,6:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
#// ⚠ ORDERING STEP (bug #1024). The leader's own When Deployed trigger and the CR 19 Plot window
#// are two simultaneous triggered abilities, so CR 7.6.9 gives the player the order. EffectStack-0
#// is the Plot window (armed first, in SWUDeployLeader); EffectStack-1 is the leader. Resolving the
#// leader FIRST is the sequence this section has always measured — the step was previously forced.
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_034
P1RESCOUNT:7
P1RESAVAILABLE:5
P1DECKCOUNT:1
P1NODECISION

---

# LeaderAction_SearchPlotDraw
#// SEC_001 Chancellor Palpatine (leader) — Action [1 resource, Exhaust]: Search the top 5 cards of your
#// deck for a card with Plot, reveal it, and draw it (rest to the bottom in a random order).
#// Deck top-5 = SEC_034 (Plot) + 4 vanilla SOR_095; only SEC_034 matches the Plot filter → drawn.
#// Costs 1 resource (2 ready → 1) and exhausts the leader.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Deck: [SEC_034 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:SEC_034

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:5
P1RESAVAILABLE:1
P1LEADER:EXHAUSTED
P1NODECISION

---

# Deploy_PlotFromHand_NotReduced
#// SEC_001 Chancellor Palpatine (deployed) — the "next Plot card costs 3 less" discount only applies to a
#// card actually played USING Plot (from the resource row). A Plot card played from HAND pays full cost.
#// Deploy arms the -3; SEC_036 Dogmatic Shock Squad (Plot, cost 6) is then played from hand for the full
#// 6 (10 ready → 4), not 3.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: SEC_036

## WHEN
- P1>DeployLeader
- P1>PlayHand:0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:1:CARDID:SEC_036
P1RESAVAILABLE:4

---

# Deploy_SmuggleCard_NotReduced
#// SEC_001 Chancellor Palpatine (deployed) — the "next Plot card costs 3 less" discount applies ONLY to
#// the Plot keyword, not Smuggle. Deploy arms the -3, then SHD_075 Covert Strength is played via Smuggle
#// [3 Vigilance]; it still costs the full 3. Resources: 10 total, cost 3 + the replaced slot's deck card
#// enters exhausted (1) = 4 exhausted → 6 available (would be 9 if the -3 wrongly hit Smuggle).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 9:SOR_095:1,1:SHD_075:1
WithP1Deck: SOR_095

## WHEN
- P1>DeployLeader
- P1>SmuggleResource:9

## EXPECT
P1LEADER:DEPLOYED
P1RESAVAILABLE:7
#// CORRECTED 2026-08-06 (Smuggle self-pay, bug #925 family): the smuggled card is itself a READY
#// resource and exhausts toward its OWN cost (CR 8.22.e). This case placed it LAST, where the old
#// index-order sweep never picked it, so it recorded one resource too many being spent.
P1NODECISION

---

# OpponentPlotPlay_NotDiscounted_Baseline
#// SEC_001 Chancellor Palpatine — the baseline for the section below. P1's Palpatine is NOT deployed, so
#// no −3 exists anywhere. P2 deploys Cal Kestis LOF_015, which opens P2's own Plot window, and plays
#// SEC_123 Unveiled Might from resources at full price: 11 ready → 5.

## GIVEN
CommonSetup: bbk/ggw/{
  myLeader:SEC_001;
  theirLeader:LOF_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 7
WithP2Resources: 1:SEC_123:1,10:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P2>DeployLeader
- P2>AnswerDecision:myResources-0

## EXPECT
P2LEADER:DEPLOYED
P2RESAVAILABLE:5
P2RESCOUNT:11

---

# OpponentPlotPlay_NotDiscountedByAFloatingPalpatineCharge
#// SEC_001 Chancellor Palpatine — "the next card YOU play using Plot" is scoped to Palpatine's
#// controller. P1 deploys Palpatine and arms the −3 but has no Plot card of their own, so the charge is
#// left floating. P2 then deploys Cal Kestis and plays SEC_123 Unveiled Might through P2's own Plot
#// window — and pays the identical 6 (11 ready → 5) as in the baseline section above, not 3 less.
#// Deploy_NextPlotCosts3Less is the positive control that the −3 does work for P1.

## GIVEN
CommonSetup: bbk/ggw/{
  myLeader:SEC_001;
  theirLeader:LOF_015;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithP1Resources: 7
WithP2Resources: 1:SEC_123:1,10:SOR_095:1
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
- P2>DeployLeader
- P2>AnswerDecision:myResources-0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1LEADER:DEPLOYED
P2LEADER:DEPLOYED
P2RESAVAILABLE:5
P2RESCOUNT:11

---

# TwoPlotPlaysInOneWindow_OnlyOneGetsTheThreeOff
#// SEC_001 Chancellor Palpatine — the −3 is for "the NEXT card you play using Plot this phase", so a
#// deploy window that plays TWO Plot cards discounts exactly one of them. P1 holds SEC_034 Cad Bane
#// (cost 5) and SEC_033 Sly Moore (cost 4) as resources. Deploying Palpatine arms the −3 and opens the
#// Plot window; both are played, for a combined 5 + 4 − 3 = 6 (10 ready → 4), not 3 (a per-card
#// discount) and not 9 (no discount at all).
#// Plot replaces each played card from the deck, so the resource row stays at 10 while the deck drops
#// from 3 to 1, and the arena holds the deployed leader plus both units.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:SEC_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SEC_034:1,1:SEC_033:1,8:SOR_095:1
WithP1Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>DeployLeader
#// ⚠ ORDERING STEP (bug #1024). The leader's own When Deployed trigger and the CR 19 Plot window
#// are two simultaneous triggered abilities, so CR 7.6.9 gives the player the order. EffectStack-0
#// is the Plot window (armed first, in SWUDeployLeader); EffectStack-1 is the leader. Resolving the
#// leader FIRST is the sequence this section has always measured — the step was previously forced.
- P1>AnswerDecision:EffectStack-1
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myResources-0

## EXPECT
P1LEADER:DEPLOYED
P1RESAVAILABLE:4
P1RESCOUNT:10
P1DECKCOUNT:1
P1GROUNDARENACOUNT:3
P1NODECISION
