# Regroup_ResourcingACard_GivesExperience
#// IC27_089 Maz Kanata, It Calls to You — Unit (Ground) 2/1, cost 2, [Command], Underworld, unique.
#// Text: Hidden. When you resource a card (including during the regroup phase): Give an Experience token to this unit.
#// COVERAGE: offer=N/A — no choice ("this unit") · decline=N/A — no "may" · negative=Regroup_NoResource_NoExperience +
#//           OpponentResources_NoExperience ("YOU resource") · quantity=Stockpile_TwoCardsResourced_TwoExperience (one
#//           trigger per card) · blanked=Blanked_NoExperience · control=Control_StolenMaz_TriggersForTheController ·
#//           reqboundary=RequestBoundary_BeforeTheRegroupResource ·
#//           PRODUCERS (CR 7.7 + the 07/20/2026 errata "Put into play as a resource" → "resource": every route into your
#//           resource zone is resourcing a card) — one section each: regroup (this) · ramp helper
#//           (Ramp_SuperlaserTechnician) · Issue a Command · Stockpile · Expendable Mercenary · Display Piece (the
#//           defeated unit's CONTROLLER resources it) · Broken Horn · King Grakchawwaa · Overgrowth · Osha ·
#//           Arquitens Assault Cruiser. (DoResourceCard — the ResourceCard macro — is hooked too but has no DSL route.)
#//           modes=2P only ("you" — self in every format).
#//
#// ⚠ PREVIEW SET — IC27 is absent from card-specific-rulings.md. Not a resource: a CONTROL change of a resource
#// (SHD_213 DJ), a Credit token, or the pregame starting resources (Maz cannot be in play). The Experience is given as the
#// card lands (no choice to make, so no observable order).
#//
#// THE POSITIVE. P1 resources a card in the regroup phase: Maz gets an Experience token (2/1 → 3/2).
## GIVEN
CommonSetup: ggk/ggw/{myhandCardIds:SOR_095}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
WithP1GroundArena: IC27_089:1:0
## WHEN
- P1>Pass
- P1>ResourceHand:0
- P2>ResourcePass
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:3

---

# Regroup_NoResource_NoExperience
#// Both players skip the regroup resource: no Experience.
## GIVEN
CommonSetup: ggk/ggw/{myhandCardIds:SOR_095}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
WithP1GroundArena: IC27_089:1:0
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# OpponentResources_NoExperience
#// "When YOU resource": P2 resourcing a card does not trigger P1's Maz.
## GIVEN
CommonSetup: ggk/ggw/{myhandCardIds:SOR_095;theirhandCardIds:SOR_095}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
WithP1GroundArena: IC27_089:1:0
## WHEN
- P1>Pass
- P1>ResourcePass
- P2>ResourceHand:0
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2RESCOUNT:1

---

# RequestBoundary_BeforeTheRegroupResource
#// The regroup resource is answered in a later request: Maz still gets the token.
## GIVEN
CommonSetup: ggk/ggw/{myhandCardIds:SOR_095}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
WithP1GroundArena: IC27_089:1:0
## WHEN
- P1>Pass
- P1>SimulateRequestBoundary
- P1>ResourceHand:0
- P2>ResourcePass
## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Stockpile_TwoCardsResourced_TwoExperience
#// LAW_171 Stockpile resources this event AND the top card of the deck: two cards, two triggers, two tokens.
## GIVEN
CommonSetup: ggk/ggw/{myResources:6;myhandCardIds:LAW_171}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_237
WithP1GroundArena: IC27_089:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESCOUNT:8
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# Blanked_NoExperience
#// A Maz that has lost her abilities (SOR_138 marker) gets nothing from the same Stockpile.
## GIVEN
CommonSetup: ggk/ggw/{myResources:6;myhandCardIds:LAW_171}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_237
WithP1GroundArena: IC27_089:1:0:SOR_138
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESCOUNT:8
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# Control_StolenMaz_TriggersForTheController
#// "you" is Maz's CONTROLLER: a Maz P2 owns but P1 controls triggers when P1 resources (Stockpile: 2 tokens).
## GIVEN
CommonSetup: ggk/ggw/{myResources:6;myhandCardIds:LAW_171}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_237
WithP1GroundArenaControlled: IC27_089:2
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_089
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2

---

# IssueACommand_ResourceThisEvent
#// IC27_087 Issue a Command: the Experience is given to the Trooper (not Maz), and resourcing the event gives Maz hers.
## GIVEN
CommonSetup: ggk/ggw/{myResources:4;myhandCardIds:IC27_087}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: SOR_222
WithP1GroundArena: [IC27_089:1:0 SEC_080:1:0]
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESCOUNT:5

---

# Ramp_SuperlaserTechnician
#// The shared ramp helper (SWURampResource*/SWUResourceTopOfDeck): Superlaser Technician dies attacking the Wampa and is
#// put into play as a resource — Maz gets a token.
## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SHD_085:1:0 IC27_089:1:0]
WithP1Resources: 2
WithP2GroundArena: SOR_164:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1RESCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:IC27_089
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# ExpendableMercenary_ResourcesItself
#// LAW_159 Expendable Mercenary's When Defeated resources it from the discard pile.
## GIVEN
CommonSetup: ggw/bgw/{myResources:0}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [LAW_159:1:0 IC27_089:1:0]
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1RESCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_089
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# DisplayPiece_TheDefeatedUnitsControllerResourcesIt
#// LAW_103 Display Piece: "Its CONTROLLER resources it" — P2 resources its own defeated Trooper, so P2's Maz triggers.
## GIVEN
CommonSetup: brk/rrk/{myResources:4;theirResources:0}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: [SEC_080:1:0 IC27_089:1:0]
WithP1Hand: LAW_103
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2RESCOUNT:1
P2GROUNDARENAUNIT:0:CARDID:IC27_089
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# BrokenHorn_ResourcesTheTopCard
#// LAW_083 Broken Horn: fewer resources than the opponent → resource the top card of the deck.
## GIVEN
CommonSetup: ryk/bgw/{myResources:5;theirResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP2Hand: [SOR_095 SOR_237 SEC_080]
WithP1Deck: [SOR_237 SOR_095]
WithP1Hand: LAW_083
WithP1GroundArena: IC27_089:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESCOUNT:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# KingGrakchawwaa_OneCardPerWookiee
#// HMW_123 King Grakchawwaa: one other friendly Wookiee → one card resourced → one token.
## GIVEN
CommonSetup: ggw/bgw/{myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SHD_211:1:0 IC27_089:1:0]
WithP1Hand: HMW_123
WithP1Deck: [SOR_237 SOR_046]
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESCOUNT:7
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1

---

# Overgrowth_ResourceThisCard
#// HMW_151 Overgrowth with no Kashyyyk base: only "Resource this card" happens — Maz gets a token.
## GIVEN
CommonSetup: ggw/bgw/{myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: IC27_089:1:0
WithP1Hand: HMW_151
## WHEN
- P1>PlayHand:0
## EXPECT
P1RESCOUNT:6
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Osha_ResourcesACardFromHand
#// HMW_017 Osha's front: after a Heroism unit died, play a Villainy unit from resources, then resource a hand card.
#// Playing FROM resources is not resourcing; the hand card going IN is — one token.
## GIVEN
CommonSetup: yyw/rrk/{myLeader:HMW_017;myhandCardIds:SOR_095}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1:HMW_055:1,1:SEC_080:1,8:SOR_046:1
WithP1GroundArena: [SOR_095:1:0 IC27_089:1:0]
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>AttackGroundArena:0:0
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-1
- P1>AnswerDecision:myHand-0
## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:CARDID:IC27_089
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Arquitens_DefeatedUnitBecomesYourResource
#// SHD_122 Arquitens Assault Cruiser attacks and defeats a TIE: the TIE goes into P1's resource zone — Maz gets a token.
## GIVEN
CommonSetup: ggk/rrk/{myResources:2}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SHD_122:1:0
WithP1GroundArena: IC27_089:1:0
WithP2SpaceArena: SOR_225:1:0
## WHEN
- P1>AttackSpaceArena:0:0
## EXPECT
P1RESCOUNT:3
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
