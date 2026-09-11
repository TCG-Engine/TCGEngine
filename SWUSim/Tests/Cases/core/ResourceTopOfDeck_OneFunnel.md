# SmuggleUnit_SlotRefill
#// SSOT #3 (gamelog-updates, 2026-09-11) — "put the top card of your deck into play as a resource" had ~20
#// hand-written copies: 8 raw Remove + AddResources loops (the Smuggle unit / event / upgrade and Plot slot
#// refills, Hunter, Frontier Trader, Bail Organa, Citadel Research Center) and a dozen callers of the ramp
#// helper that located the top card themselves — several by assuming 'myDeck-0' is live, the assumption
#// that once made Outlaw Corona's bounty silently do nothing. Their log lines disagreed ("put a card into
#// play as a resource" / "resourced the top card of their deck", with or without a source). They now share
#// SWUResourceTopOfDeck, and every one reads "P1 resourced the top card of their deck (<source or reason>)".
#// One section per site, so each conversion is pinned on its own.
#// The Smuggle refill of a smuggled UNIT (SHD_065). (Fixture from sec/BailOrgana_DoingEverythingHeCan.md.)

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_008:1:1:1;
  myBase:JTL_019;
  myBaseDamage:2;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:SHD_065:1,8:SOR_095:1
WithP1Deck: [SOR_128]

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1SPACEARENACOUNT:1
P1RESCOUNT:9
P1DECKCOUNT:0
LOGCOUNT:1:resourced the top card of their deck
LOGCONTAINS:P1 resourced the top card of their deck (Smuggle slot refill)

---

# SmuggleEvent_SlotRefill
#// The Smuggle refill of a smuggled EVENT (SHD_252 Smuggler's Aid) — its own branch, which replaces the slot
#// BEFORE the event resolves (CR 8.22.g). (Fixture from shd/SmugglersAid.md.)

## GIVEN
CommonSetup: gyw/gyw/{myBaseDamage:5}
P1OnlyActions: true
WithP1Resources: 1:SHD_252:1,5:SOR_095:1
WithP1Deck: [SOR_128]

## WHEN
- P1>SmuggleResource:0

## EXPECT
P1BASEDMG:2
P1RESCOUNT:6
P1DECKCOUNT:0
LOGCOUNT:1:resourced the top card of their deck
LOGCONTAINS:P1 resourced the top card of their deck (Smuggle slot refill)

---

# SmuggleUpgrade_SlotRefill
#// The Smuggle refill of a smuggled UPGRADE (SHD_174) — a third branch, in the SMUGGLE_ATTACH continuation.
#// (Fixture from shd/RazorCrest_ReliableGunship.md.)

## GIVEN
CommonSetup: yyw/yyw
P1OnlyActions: true
WithP1GroundArena: SOR_095:0:0
WithP1Resources: 3:SOR_046:1,1:SHD_174:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:3

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESCOUNT:4
P1DECKCOUNT:0
LOGCOUNT:1:resourced the top card of their deck
LOGCONTAINS:P1 resourced the top card of their deck (Smuggle slot refill)

---

# Plot_SlotRefill
#// The Plot refill (_SWUPlotReplaceSlot, CR 19.c). (Fixture from core/GameLog_Plays.md.)

## GIVEN
CommonSetup: yyk/rrk/{myLeader:SOR_005;myResources:5}
P1OnlyActions: true
WithP1Resources: 1:SEC_053:1
WithP1Deck: [SOR_095 SOR_128]
WithP2GroundArena: SOR_037:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myResources-5
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESCOUNT:6
P1DECKCOUNT:1
LOGCOUNT:1:resourced the top card of their deck
LOGCONTAINS:P1 resourced the top card of their deck (Plot slot refill)

---

# FrontierTrader_Ramp
#// SHD_214 Frontier Trader: "When Played: You may return a resource you control to its owner's hand. If you
#// do, you may put the top card of your deck into play as a resource." A raw copy that logged "put a card
#// into play as a resource". (Fixture from shd/FrontierTrader.md.)

## GIVEN
CommonSetup: yyw/yyw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_214
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:YES

## EXPECT
P1RESCOUNT:4
P1DECKCOUNT:0
LOGCOUNT:1:resourced the top card of their deck
LOGCONTAINS:P1 resourced the top card of their deck ([[SHD_214|Frontier Trader]])

---

# Hunter_Ramp
#// SHD_009 Hunter (deployed On Attack): return a resource, then put the top card of your deck into play as a
#// resource. A raw copy. (Fixture from shd/Hunter_OutcastSergeant.md.)

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Resources: 6:SOR_046:1,1:SOR_179:1
WithP1Deck: SOR_095

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myResources-6

## EXPECT
P1DECKCOUNT:0
LOGCOUNT:1:resourced the top card of their deck
LOGCONTAINS:P1 resourced the top card of their deck ([[SHD_009|Hunter]])

---

# CitadelResearchCenter_Ramp
#// LAW_029 Citadel Research Center (base Epic Action): return a resource to hand, then resource the top card
#// of your deck. A raw copy whose line had no source. (Fixture from law/CitadelResearchCenter.md.)

## GIVEN
CommonSetup: ybw/grw/{
  myBase:LAW_029
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Deck: SOR_128

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myResources-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
LOGCOUNT:1:resourced the top card of their deck
LOGCONTAINS:P1 resourced the top card of their deck ([[LAW_029|Citadel Research Center]])

---

# ResupplyCarrier_Ramp
#// JTL_119 Resupply Carrier — a ramp-helper caller that found the top card by hand; its line read "put a
#// card into play as a resource". (Fixture from jtl/ResupplyCarrier.md.)

## GIVEN
CommonSetup: ggw/bbk/{
  myLeader:JTL_007;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_119
WithP1Resources: 6
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1RESCOUNT:7
P1DECKCOUNT:0
LOGCOUNT:1:resourced the top card of their deck
LOGCONTAINS:P1 resourced the top card of their deck ([[JTL_119|Resupply Carrier]])

---

# OutlawCorona_BountyRamp
#// SHD_116 Outlaw Corona's Bounty (engine collector): "put the top card of your deck into play as a
#// resource" — the 'myDeck-0' caller whose dead-slot assumption once made it silently do nothing. The
#// collector (P1) resources. (Fixture from shd/OutlawCorona.md.)

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SHD_116:1:1
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1RESCOUNT:1
P1RESAVAILABLE:0
P1DECKCOUNT:0
LOGCOUNT:1:resourced the top card of their deck
LOGCONTAINS:P1 resourced the top card of their deck ([[SHD_116|

---

# GalacticEscalation_EachPlayer
#// TS26_56 Galactic Escalation: "Each player puts the top card of their deck into play as a resource." One
#// line per player. (Fixture from ts26/GalacticEscalation.md.)

## GIVEN
CommonSetup: ggk/rrk/{myResources:2;theirResources:1;handCardIds:TS26_56}
WithP1Deck: [SEC_080 SOR_095]
WithP2Deck: [SOR_046 SOR_128]
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1RESCOUNT:3
P2RESCOUNT:2
LOGCOUNT:2:resourced the top card of their deck
LOGCONTAINS:P1 resourced the top card of their deck ([[TS26_56|
LOGCONTAINS:P2 resourced the top card of their deck ([[TS26_56|

---

# TearThisShipApart_OpponentRefills
#// LAW_066 Tear This Ship Apart: "…If you do, that opponent resources the top card of their deck." The
#// OPPONENT resources, from their own deck. (Fixture from law/TearThisShipApart.md StealEvent.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 13
WithP1Hand: LAW_066
WithP2Resources: 1:LAW_244:1
WithP2Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirResources-0

## EXPECT
P2RESCOUNT:1
P2DECKCOUNT:0
LOGCOUNT:1:resourced the top card of their deck
LOGCONTAINS:P2 resourced the top card of their deck ([[LAW_066|Tear This Ship Apart]])
