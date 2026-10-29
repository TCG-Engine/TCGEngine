# Front_DiscardThenGiveExperience
#// IC27_003 Kylo Ren — Supreme Leader (Leader, Command/Villainy, Force·First Order·Official; deploys 5/3 at 5)
#// FRONT: "Action [Exhaust, discard a card from your hand]: Give an Experience token to a unit."
#// DEPLOYED: "When Deployed: You may return a unit from your discard pile to your hand."
#// FRONT is SHD_011 Kylo Ren's cost word for word: the discard is a COST (inside the brackets) — an empty hand makes
#// the Action unavailable (SWULeaderActionAffordable), the pick is mandatory and goes through DoDiscardCard (the
#// full discard ceremony). Then a mandatory pick over EVERY unit on the table ("a unit") -> GIVE_EXPERIENCE.
#// DEPLOYED: $whenPlayedAbilities["IC27_003:0"] — an optional pick (MZMAYCHOOSE) over the UNIT cards in the
#// resolver's own discard -> back to hand. Epic deploy needs no wiring (threshold = printed cost 5).
#//
#// COVERAGE: offer=Front_Offer_ExperienceToAnyUnit + Front_Offer_DiscardFromHand + Deployed_Offer_UnitCardsOnly ·
#//           decline=Deployed_Decline_NothingReturned (front: N/A — the cost pick and the target pick are
#//           mandatory) ·
#//           boundary=Front_EmptyHand_Unavailable (0 cards) paired with Front_DiscardThenGiveExperience (cards in
#//           hand); Deployed_NoUnitInDiscard_NoPrompt (0 unit cards) ·
#//           control=N/A (a leader cannot be stolen; "your hand"/"your discard pile" are the leader's controller's) ·
#//           reqboundary=Front_AnsweredAcrossRequestBoundary + Deployed_AnsweredAcrossRequestBoundary ·
#//           modes=2P only (no player reference; "a unit" is the whole table, the rest is "your")
#//
#// P1 discards the Battlefield Marine and gives the Experience token to P2's Imperial Dark Trooper.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_128]
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1LEADER:EXHAUSTED
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_095
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Front_Offer_DiscardFromHand
#// The cost pick is over P1's hand (both cards), left pending.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_128]
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myHand-0&myHand-1
P1LEADER:EXHAUSTED

---

# Front_Offer_ExperienceToAnyUnit
#// "a unit" — the whole table: P1's ground and space units and P2's ground unit and deployed leader unit.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003;theirLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095]
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirGroundArena-1
P1HANDCOUNT:0

---

# Front_EmptyHand_Unavailable
#// The discard is a COST: with an empty hand it cannot be paid, so nothing happens — the leader stays ready and no
#// token is given.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Front_NoUnitInPlay_UsableButGivesNothing
#// The cost changes the game state (CR 6.4.587.c), so with a card in hand and no unit anywhere the Action is
#// usable: Kylo exhausts, the card is discarded (the lone card auto-resolves), and no token is given.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095]

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1LEADER:EXHAUSTED
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# Front_DiscardIsARealDiscard_SilencerBecomesPlayable
#// The cost discard is a real "discarded from your hand" event: discarding IC27_063 Kylo's TIE Silencer stamps its
#// own play-from-discard permission, and P1 replays it (cost 4) the same phase.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003;myResources:4}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [IC27_063]
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>UseLeaderAbility
- P1>PlayFromDiscard:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:IC27_063
P1RESAVAILABLE:0

---

# Front_ClosesTheActionOnce

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: [SOR_095 SOR_128]
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
TURNPLAYER:2
NOEXTRAACTION

---

# Front_AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_095 SOR_128]
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myHand-1
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1DISCARDUNIT:0:CARDID:SOR_128
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# Deployed_ReturnsAUnitFromDiscard
#// DeployLeader (5 resources): the When Deployed offers the unit cards in P1's discard; P1 returns the Dark Trooper.

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003;myResources:5;discardCardIds:SEC_080,SOR_251}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1LEADER:DEPLOYED
P1GROUNDARENAUNIT:0:CARDID:IC27_003
P1HANDCOUNT:1
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SOR_251
P1NODECISION

---

# Deployed_Offer_UnitCardsOnly
#// Only UNIT cards: the Dark Trooper and the TIE/ln — not Confiscate (an event).

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003;myResources:5;discardCardIds:SEC_080,SOR_251,SOR_225}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myDiscard-0&myDiscard-2

---

# Deployed_Decline_NothingReturned

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003;myResources:5;discardCardIds:SEC_080}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:-

## EXPECT
P1LEADER:DEPLOYED
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION

---

# Deployed_NoUnitInDiscard_NoPrompt

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003;myResources:5;discardCardIds:SOR_251}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader

## EXPECT
P1LEADER:DEPLOYED
P1NODECISION
P1HANDCOUNT:0

---

# Deployed_ClosesTheActionOnce

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003;myResources:5;discardCardIds:SEC_080}
SkipPreGame: true
WithActivePlayer: 1

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
TURNPLAYER:2
NOEXTRAACTION

---

# Deployed_AnsweredAcrossRequestBoundary

## GIVEN
CommonSetup: ggk/rrk/{myLeader:IC27_003;myResources:5;discardCardIds:SEC_080}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>DeployLeader
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myDiscard-0

## EXPECT
P1HANDCOUNT:1
P1DISCARDCOUNT:0
