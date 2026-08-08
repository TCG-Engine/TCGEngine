# BaseHit_MillAndPlayFromDiscard
#// SEC_205 Obi-Wan Kenobi (Unit, 4/5, cost 4, Cunning/Heroism, Force/Jedi/Republic, Ground)
#//   "When this unit deals combat damage to a base: Discard a card from the defending player's deck. For
#//    this phase, you may play that card from their discard pile, ignoring its aspect penalties."
#// SEC_205 attacks P2's base for 4 → mills the top of P2's deck (SOR_095) into P2's discard with the OTPN
#// modifier (play-from-opp-discard at cost, ignoring aspect penalty). P1 then plays SOR_095 from P2's
#// discard. SOR_095 is Command/Heroism — fully off-aspect for Cunning P1 (penalty would be +4), but OTPN
#// ignores it, so P1 pays exactly its cost (2). P1 has exactly 2 ready resources: the play succeeds and
#// ends at 0 ready — which it could NOT if the +4 penalty applied (proving the aspect-penalty bypass).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 2
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P2DISCARDCOUNT:0
P1RESAVAILABLE:0
P2DECKCOUNT:2

---

# OverwhelmCoordinateHitsBase_MillFires
#// SEC_205 fires on ANY combat damage to a base — including excess Overwhelm damage. P1 controls Wampa
#// (SOR_164), Obi-Wan (SEC_205) and Clone Commander Cody (TWI_114). Cody's Coordinate (3+ units) gives
#// every OTHER friendly unit +1/+1 and Overwhelm, so Obi-Wan is 5/6 with Overwhelm. Obi-Wan attacks
#// SpecForce Soldier (SOR_140, 2/2): kills it and spills 5−2=3 overflow onto P2's base. That base hit
#// fires SEC_205 → mill SOR_232 (AT-ST) from P2's deck into P2's discard. P2's discard then holds both the
#// defeated SpecForce Soldier and the milled AT-ST; the deck drops from 3 to 2.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 6
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SEC_205:1:0
WithP1GroundArena: TWI_114:1:0
WithP2GroundArena: SOR_140:1:0
WithP2Deck: [SOR_232 SOR_232 SOR_232]

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:3
P2DISCARDCOUNT:2
P2DECKCOUNT:2

---

# OtherFriendlyHitsBase_NoMill
#// SEC_205's trigger is "When THIS unit deals combat damage to a base" — a different friendly unit hitting
#// the base does nothing. P1's Wampa (SOR_164, 4 power) attacks P2's base for 4 while Obi-Wan sits idle.
#// No card is milled: P2's deck stays full, its discard stays empty, and no play-from-discard is offered.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1Resources: 3
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SEC_205:1:0
WithP2Deck: [SOR_232 SOR_232]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P2DECKCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION

---

# ObiwanHitsUnit_NoBaseMill
#// SEC_205 only fires on damage to a BASE. Obi-Wan (4/5) attacks Consular Security Force (SOR_046, 3/7):
#// he deals 4 combat damage to the unit (not lethal) and takes 3 back, but no base is hit — so nothing is
#// milled. P2's deck stays full and its discard stays empty.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP2GroundArena: SOR_046:1:0
WithP2Deck: [SOR_232 SOR_232]

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P2DECKCOUNT:2
P2DISCARDCOUNT:0

---

# EnemyHitsBase_NoMill
#// SEC_205 belongs to P1's Obi-Wan; an ENEMY unit dealing combat damage to P1's base must not fire it.
#// P1 passes; P2's A-Wing (SEC_213, 1/2 Raid 1 → 2 power attacking a base) hits P1's base for 2. Neither
#// deck is milled — P1's deck and P2's deck both stay full and no discard grows.

## GIVEN
CommonSetup: yyk/rrk
WithActivePlayer: 1
WithP1GroundArena: SEC_205:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_232 SOR_232]

## WHEN
- P1>Pass
- P2>AttackSpaceArena:0:BASE

## EXPECT
P1BASEDMG:2
P1DECKCOUNT:2
P2DECKCOUNT:2
P2DISCARDCOUNT:0

---

# EmptyDeck_NoDiscardOffered
#// SEC_205 hits the base but the defending player's deck is empty — there is nothing to discard, so the
#// ability fizzles cleanly. Obi-Wan attacks P2's empty-deck base for 4; no card is milled, no play-from-
#// discard offer appears.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP2Deck: []

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P2DECKCOUNT:0
P2DISCARDCOUNT:0
P1NODECISION

---

# PlaysOpponentEventFromDiscard_PowerOfDarkSide
#// SEC_205 Obi-Wan — a card milled to the opponent's discard may be PLAYED from there this phase; when it is
#// an EVENT it resolves under the CASTER (P1). P1 mills + plays SOR_041 Power of the Dark Side ("An opponent
#// chooses a unit they control. Defeat that unit.") from P2's discard: "an opponent" is the caster's opponent
#// (P2), who defeats their own only unit (SOR_046).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 3
WithP2Deck: [SOR_041 SOR_095]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4

---

# PlaysOpponentEventFromDiscard_Resupply_ResourceForCaster
#// SEC_205 Obi-Wan — an opponent's EVENT played from their discard resolves under the CASTER. SOR_126
#// Resupply ("Put this event into play as a resource") milled from P2's deck and played by P1 becomes a
#// resource for P1 (the caster), not P2. P1 pays SOR_126's cost (3; OTPN ignores the aspect penalty) and
#// ends with one MORE resource than before.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 3
WithP2Deck: [SOR_126 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0

## EXPECT
P1RESCOUNT:4
P2BASEDMG:4

---

# ForeignEventPlay_AppliesSawGerreraTax
#// Behavior change: a foreign-owned event play (routed through ActivateCard) now applies the same
#// play-time taxes as any event play. P2 controls Saw Gerrera SOR_153 ("as an additional cost for each
#// opponent to play an event, deal 2 to their base") — P1 is P2's opponent, so P1 playing an event pays 2
#// to P1's base. P1's Obi-Wan mills LAW_244 (Unmarked Credits, cost 1) from P2's deck with the OTPN
#// modifier, then P1 plays it: P1's base takes 2 (Saw Gerrera) AND P1 gets a Credit token (event resolved).
#// (Before routing through ActivateCard the bypass path skipped Saw Gerrera → P1BASEDMG was 0.)

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP2GroundArena: SOR_153:1:0
WithP1Resources: 2
WithP2Deck: [LAW_244 LAW_244 LAW_244]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0

## EXPECT
P1BASEDMG:2
P2BASEDMG:4
P1CREDITCOUNT:1
P2DECKCOUNT:2

---

# MilledCard_NotPlayableNextPhase
#// SEC_205 Obi-Wan Kenobi — the permission is "FOR THIS PHASE, you may play that card from their discard
#// pile". An unspent permission must not survive into the next phase. Obi-Wan hits P2's base and mills
#// SOR_095, but P1 does NOT play it; both players then pass through regroup into the next action phase and
#// P1 tries again — the card stays in P2's discard and P1's resources are untouched.
## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 2
WithP1Deck: [SOR_095 SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095 SOR_095]
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass
- P1>PlayFromOpponentDiscard:0
## EXPECT
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:2


---

# PermissionIsConsumedByTheFirstPlay_CannotReplayAfterItReturnsToTheDiscard
#// SEC_205 Obi-Wan Kenobi — "you may play THAT CARD from their discard pile" is a single permission, not
#// a standing licence on the card. P1 mills SOR_095 and plays it out of P2's discard; P2 then defeats it
#// with SOR_078 Vanquish, putting the very same card back in P2's discard. P1 cannot play it a second
#// time — the attempt is a no-op, the card stays in P2's discard and P1's resources are untouched.

## GIVEN
CommonSetup: yyk/bbk
WithActivePlayer: 1
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 12
WithP2Resources: 6
WithP2Hand: SOR_078
WithP2Deck: [SOR_095 SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>Pass
- P1>PlayFromOpponentDiscard:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>PlayFromOpponentDiscard:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_205
P2DISCARDCOUNT:2
P1RESAVAILABLE:10

---

# MilledUnitEntersUnderTheCaster_AndItsOwnAbilitiesWork
#// SEC_205 Obi-Wan Kenobi — a milled UNIT played out of the opponent's discard enters under the CASTER and
#// brings its whole ability set with it. Obi-Wan hits P2's base and mills SHD_122 Arquitens Assault Cruiser
#// (8 cost, Command — fully off-aspect for Cunning P1, so the +2 penalty would make it unaffordable at 8
#// ready; OTPN ignores it and P1 pays exactly 8). The Arquitens enters P1's SPACE arena, its Ambush fires
#// for P1 (an immediate attack), and its own "When this unit attacks and defeats a non-leader unit: Put the
#// defeated unit into play as a resource under your control" resources P2's A-Wing FOR P1 — resource count
#// 8 → 9. ⚠ The Ambush offer only surfaces after a drain on this path.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 8
WithP2SpaceArena: SOR_141:1:0
WithP2Deck: [SHD_122 SHD_122]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>Drain
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_122
P2SPACEARENACOUNT:0
P1RESCOUNT:9
P1RESAVAILABLE:0
P2DECKCOUNT:1

---

# MilledUnit_AmbushDeclined_StillEntersUnderTheCaster
#// SEC_205 Obi-Wan Kenobi — the decline half of the section above. Declining the milled Arquitens' Ambush
#// leaves P2's A-Wing alive and unresourced, but the unit itself is still P1's and still cost exactly 8
#// (the aspect penalty stayed ignored). This separates "the play worked" from "the Ambush chain worked".

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 8
WithP2SpaceArena: SOR_141:1:0
WithP2Deck: [SHD_122 SHD_122]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>Drain
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SHD_122
P2SPACEARENACOUNT:1
P1RESCOUNT:8
P1RESAVAILABLE:0

---

# MilledEventSearchesTheCASTERSDeck_AndItsPermissionLandsInTheCASTERSDiscard
#// SEC_205 Obi-Wan Kenobi — an opponent's EVENT played from their discard resolves entirely under the
#// CASTER, including the zones its own text calls "your". TWI_201 Aid from the Innocent ("Search the top
#// 10 cards of YOUR deck for 2 Heroism non-unit cards and discard them... For this phase, you may play the
#// discarded cards, and they each cost 2 resources less") is milled from P2's deck and played by P1: the
#// search reads P1's deck (only SOR_199 matches — SOR_141 is a Heroism UNIT and SEC_080 is off-aspect),
#// the pick lands in P1's discard, and P2's deck is untouched at 1. The event card itself goes to its
#// OWNER's discard (P2's). P1 spends all 5 resources on it, then plays the discarded Bamboozle with ZERO
#// ready resources — only possible because the "cost 2 less" permission (2 - 2 = 0) followed the caster
#// too. Bamboozle then resolves for real: Obi-Wan's SOR_120 goes back to P1's hand and Bamboozle returns
#// to P1's discard. Obi-Wan hit for 6, not 4, because SOR_120 was still attached during the attack.

## GIVEN
CommonSetup: yyw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP1Resources: 5
WithP1Deck: [SOR_199 SOR_141 SEC_080 SEC_080 SEC_080]
WithP2Deck: [TWI_201 TWI_201]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>AnswerDecision:SOR_199
- P1>PlayFromDiscard:0

## EXPECT
P2BASEDMG:6
P1DECKCOUNT:4
P2DECKCOUNT:1
P2DISCARDCOUNT:1
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1

---

# MilledPilotingCard_OffersUnitOrPilot_PilotPaysThePilotingCost
#// SEC_205 Obi-Wan Kenobi — the permission is "you may play that CARD from their discard pile", not "that
#// unit", so a milled card with Piloting may also be played as an UPGRADE on a friendly Vehicle. Obi-Wan
#// mills JTL_142 Darth Vader (Scourge of Squadrons — unit cost 6, Piloting [3, Aggression/Villainy]) and
#// P1 is offered the Unit-or-Pilot choice. Taking Pilot attaches him to P1's Vehicle for exactly 3: the
#// Piloting cost with the aspect penalty ignored (P1 is Cunning/Villainy, so Aggression is uncovered and
#// the unwaived pilot cost would be 5). P1 keeps its ground arena at 1 — Vader never became a unit.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1SpaceArena: SOR_141:1:0
WithP1Resources: 8
WithP2Deck: [JTL_142 JTL_142]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>AnswerDecision:Pilot

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_141
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_142
P1RESAVAILABLE:5
P2DISCARDCOUNT:0

---

# MilledPilotingCard_UnitBranchPaysTheUnitCost
#// SEC_205 Obi-Wan Kenobi — the other half of the same fork, and the control that proves BOTH options are
#// genuinely offered. Same board, but P1 answers Unit: Vader enters P1's GROUND arena as a unit for 6
#// (again with the aspect penalty ignored — it would be 8 otherwise) and the Vehicle stays bare.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1SpaceArena: SOR_141:1:0
WithP1Resources: 8
WithP2Deck: [JTL_142 JTL_142]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0
- P1>AnswerDecision:Unit

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_142
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1RESAVAILABLE:2

---

# MilledPilotAttachedAsUpgrade_IsStillOWNEDByTheOpponent
#// SEC_205 Obi-Wan Kenobi — a card played from an opponent's discard is CONTROLLED by the caster but still
#// OWNED by that opponent, and the Pilot-upgrade route must keep that split (the unit route already does).
#// P1 attaches the milled Vader to its A-Wing, then P2 Vanquishes the host: the A-Wing (P1's card) goes to
#// P1's discard while Vader goes back to P2's — P2's discard ends at 2 (Vanquish + Vader), P1's at 1.

## GIVEN
CommonSetup: yyk/bbk/{myResources:8;theirResources:5;theirHandCardIds:SOR_078}
WithP1GroundArena: SEC_205:1:0
WithP1SpaceArena: SOR_141:1:0
WithP2Deck: [JTL_142 JTL_142]

## WHEN
- P1>AttackGroundArena:0:BASE
- P2>Pass
- P1>PlayFromOpponentDiscard:0
- P1>AnswerDecision:Pilot
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P2DISCARDCOUNT:2

---

# MilledPilotingCard_NoFriendlyVehicle_PlaysAsAUnitWithNoFork
#// SEC_205 Obi-Wan Kenobi — the Unit-or-Pilot fork must only appear when the Pilot route is actually legal.
#// Same milled Vader, but P1 controls no Vehicle (Obi-Wan is Force/Jedi/Republic), so there is no eligible
#// host: no choice is raised and the card simply plays as a unit for 6. This also guards that the
#// aspect-penalty waiver pre-loaded for the pilot cost does not leak into the unit price.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_205:1:0
WithP1Resources: 8
WithP2Deck: [JTL_142 JTL_142]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayFromOpponentDiscard:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:JTL_142
P1RESAVAILABLE:2
P1NODECISION
