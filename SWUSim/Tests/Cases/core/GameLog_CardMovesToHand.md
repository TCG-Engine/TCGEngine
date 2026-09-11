# SearchToHand_InvisibleHand_RevealedAndDrawn
#// Game-log sweep follow-up — card handlers that move cards with a raw AddHand / MZMove / ->Damage never
#// reached a logging funnel, so the move left no game-log line (2026-09-11). Each section below pins the
#// line one such handler now writes.
#// JTL_089 The Invisible Hand: "When Played: You may search the top 8 cards of your deck for a Droid unit,
#// REVEAL it, and draw it. If it costs 2 or less, you may play it for free." Its own finalize (JTL_089#0)
#// replaces TOPDECKSEARCH_FINALIZE, so it wrote no draw line. The card says "reveal", so the drawn Droid is
#// named publicly. (Fixture from jtl/TheInvisibleHand_CrawlingWithVultures.md WhenPlayed_SearchDroidDrawOnly.)

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_089
WithP1Resources: 6
WithP1Deck: [LOF_158 SOR_095 SOR_237]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:LOF_158

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1 revealed and drew [[LOF_158|Hyena Bomber]] ([[JTL_089|The Invisible Hand]])
P2LOGSEES:revealed and drew [[LOF_158

---

# SearchToHand_SenseThroughTheForce_RevealedAndDrawn
#// ASH_235 Sense Through the Force: "Choose a number, then search the top 5 cards of your deck for a card,
#// REVEAL it, and draw it. If its cost is the chosen number, …" Its own finalize (ASH_235#1) wrote no draw
#// line. "Reveal" → named publicly. (Fixture from ash/SenseThroughTheForce.md CostMismatch_NoAdvantage.)

## GIVEN
CommonSetup: yyk/yyk/{myResources:2;handCardIds:ASH_235}
WithP1GroundArena: SOR_049:1:0
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
WithP1Deck: SOR_095
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:5
- P1>AnswerDecision:SOR_046

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1 revealed and drew [[SOR_046|Consular Security Force]] ([[ASH_235|Sense Through the Force]])
P2LOGSEES:revealed and drew [[SOR_046

---

# SearchToHand_CaptainVaughn_HiddenDraw
#// TS26_39 Captain Vaughn: "When Defeated: Search the top 3 cards of your deck for a card and draw it. Then,
#// put a card from your hand on top of your deck." No "reveal" — the draw is HIDDEN: a public count, and the
#// card named only to P1. The opponent must never see its name. (Fixture from
#// ts26/CaptainVaughn_SearchTheTunnels.md WhenDefeatedSearchDrawThenTop.)

## GIVEN
CommonSetup: bbw/rrk/{handCardIds:SEC_080}
WithP1GroundArena: TS26_39:1:1
WithP2GroundArena: LAW_124:1:0
WithP1Deck: [SOR_095 SOR_046 SOR_128]
P1OnlyActions: true

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:SOR_095
- P1>AnswerDecision:myHand-0

## EXPECT
P1DECKTOPCARD:SEC_080
LOGCONTAINS:P1 drew a card ([[TS26_39|Captain Vaughn]])
P1LOGSEES:You drew [[SOR_095|Battlefield Marine]]
P2LOGNOTSEES:[[SOR_095

---

# DiscardToHand_EmperorsLegion
#// SOR_091 The Emperor's Legion: "Return each unit in your discard pile that was defeated this phase to your
#// hand." A discard pile is public, so the returned unit is named. P1's SOR_128 trades with P2's SEC_080,
#// then the event returns it. (Fixture from sor/TheEmperorsLegion.md ReturnDefeatedThisPhase.)

## GIVEN
CommonSetup: ggk/rrk/{myResources:3;handCardIds:SOR_091}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1's [[SOR_091|The Emperor's Legion]] returned [[SOR_128|Death Star Stormtrooper]] from P1's discard pile to their hand

---

# DiscardToHand_AdmiralTrench
#// TWI_086 Admiral Trench: "When Played: Return up to 3 units that were defeated this phase from your discard
#// pile to your hand." (Fixture from twi/AdmiralTrench_HoldingTheLine.md WhenPlayed_ReturnsDefeated.)

## GIVEN
CommonSetup: gyk/grw/{myResources:7;handCardIds:TWI_086}
P1OnlyActions: true
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1's [[TWI_086|Admiral Trench]] returned [[SOR_128|Death Star Stormtrooper]] from P1's discard pile to their hand

---

# DiscardToHand_FlightOfTheInquisitor_BothReturns
#// LOF_240 Flight of the Inquisitor: "You may return a Force unit and a Lightsaber upgrade from your discard
#// pile to your hand." Two independent returns, two lines (LOF_240#1 and LOF_240#3). (Fixture from
#// lof/FlightOfTheInquisitor.md ReturnTwo.)

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_240;discardCardIds:LOF_050,SOR_053}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myDiscard-0
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:2
LOGCONTAINS:P1's [[LOF_240|Flight of the Inquisitor]] returned [[LOF_050|Plo Koon]] from P1's discard pile to their hand
LOGCONTAINS:P1's [[LOF_240|Flight of the Inquisitor]] returned [[SOR_053|Luke's Lightsaber]] from P1's discard pile to their hand

---

# DiscardToHand_BoShek_OddCostReturned
#// JTL_215 BoShek: "When played as an upgrade: Discard 2 cards from your deck. Return each of those cards
#// with an odd cost to your hand." Both discards already log (SWUAddToDiscard 'DECK'); the odd card's return
#// from the discard pile did not. (Fixture from jtl/Boshek_CharismaticSmuggler.md AsUpgrade_MillReturnOdd.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 12
WithP1Hand: JTL_215
WithP1SpaceArena: SOR_044:1:0
WithP1Deck: SOR_225
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot

## EXPECT
P1HANDCOUNT:1
LOGCONTAINS:P1 discarded [[SOR_225|TIE/ln Fighter]] from their deck ([[JTL_215|BoShek]])
LOGCONTAINS:P1's [[JTL_215|BoShek]] returned [[SOR_225|TIE/ln Fighter]] from P1's discard pile to their hand

---

# ResourceToHand_Lando_NeverNamed
#// SOR_197 Lando Calrissian: "When Played: Return up to 2 friendly resources to their owners' hands."
#// Resources are FACE DOWN — each return is "a resource", never the card. (Fixture from
#// sor/LandoCalrissian_ResponsibleBusinessman.md Return2Resources.)

## GIVEN
CommonSetup: yyw/rrk/{myResources:8;handCardIds:SOR_197}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1RESCOUNT:6
LOGCOUNT:2:P1 returned a resource to their hand ([[SOR_197|Lando Calrissian]])

---

# ResourceToHand_Intimidator_NeverNamed
#// LAW_140 Intimidator: "When Played: Return any number of friendly resources to their owners' hands. For
#// each resource returned this way, create a Credit token." Face down → a count per resource, never named.
#// (Fixture from law/Intimidator_CitadelOverwatch.md ReturnResourcesForCredits.)

## GIVEN
CommonSetup: grk/bgw/{myResources:11}
WithP1Hand: LAW_140

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myResources-0&myResources-1

## EXPECT
P1RESCOUNT:9
P1CREDITCOUNT:2
LOGCOUNT:2:P1 returned a resource to their hand ([[LAW_140|Intimidator]])

---

# DiscardFromHand_Echo
#// SHD_099 Echo: "When Played: You may discard a card from your hand. Give 2 Experience tokens to a unit in
#// play with the same name as the discarded card." The discard is a raw MZMove, so no discard line was
#// written. A discarded card is revealed, so the line is public. (Fixture from shd/Echo_Restored.md
#// WhenPlayed_DiscardNameMatch_Give2Exp.)

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1Hand: SHD_099
WithP1Hand: SOR_095
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myHand-0

## EXPECT
P1DISCARDCOUNT:1
LOGCONTAINS:P1 discarded [[SOR_095|Battlefield Marine]] ([[SHD_099|Echo]])

---

# Heal_Qira_HealAllDamage
#// SHD_002 Qi'ra (deployed): "When Deployed: Heal all damage from each unit. Then, deal damage to each unit
#// equal to half its remaining HP, rounded down." The heal-all sets Damage = 0 directly, so the heal funnel
#// (and its line) never ran. SOR_046 had 4 damage. (Fixture from shd/Qira_IAloneSurvived.md
#// Qira_Deployed_HealAllThenHalfHP.)

## GIVEN
CommonSetup: bbk/bbk/{myLeader:SHD_002;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:4
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>DeployLeader

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
LOGCONTAINS:P1's [[SHD_002|Qi'ra]] healed 4 damage from P1's [[SOR_046|Consular Security Force]]

---

# ForceDefeated_HanSolo_AsTheCost
#// LAW_017 Han Solo: "Action [Exhaust, defeat a friendly token]: Deal 1 damage to a unit." The Force token is
#// a friendly token; paying with it DEFEATS it (RemoveGlobalEffect, not UseTheForce — it is not "used"), so
#// no Force line was written. (Fixture from law/HanSolo_IGotAReallyGoodFeeling.md Front_DefeatForceToken.)

## GIVEN
CommonSetup: yyw/grw/{
  myLeader:LAW_017;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:Force

## EXPECT
P1NOFORCE
LOGCONTAINS:P1 defeated their Force token ([[LAW_017|Han Solo]])
