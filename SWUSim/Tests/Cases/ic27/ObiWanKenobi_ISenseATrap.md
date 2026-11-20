# PlayFromDiscard_TheUnitDefeatedThisPhase_CostsFiveLess
#// IC27_042 Obi-Wan Kenobi — I sense a Trap. Unit, cost 8, [Vigilance][Heroism], 5/9, Force/Jedi/Republic, unique.
#// "When Played: You may choose one:
#//   • Play a unit that was defeated this phase from your discard pile. It costs [5] less.
#//   • Choose a unit that entered play this phase. It gets -5/-5 for this phase."
#// "You may choose one" = an OPTIONCHOOSE of the viable modes plus Pass. Mode 1 is TWI_189 Unnatural Life's pool
#// (a unit in YOUR discard that was defeated THIS phase) at -5, with no "enters ready" rider; mode 2 is TWI_052
#// Hello There at -5/-5. A mode that could only fizzle is not offered.
#// Fixture: CommonSetup bbw = Vigilance base + Vigilance/Heroism leader, so Obi-Wan costs his printed 8.
#// Here JTL_251 (cost 6, Heroism — in aspect) dies attacking this phase; 9 resources = 8 + exactly the 1 it costs.
#// It enters EXHAUSTED: unlike Unnatural Life / Nightbrother, Obi-Wan grants no "enters play ready".
#//
#// COVERAGE: offer=Offer_OnlyUnitsDefeatedThisPhase_FromYourDiscard (a seeded same-CardID copy is excluded) +
#//                 EnteredPlayDebuff_Offer_EnteredThisPhaseOnly (a fixture-placed unit is excluded)
#//           decline=Decline_Pass_NothingHappens; no-target=SeededDiscardUnit_NotDefeatedThisPhase_ModeNotOffered,
#//                 EnemyUnitDefeatedThisPhase_InTheirDiscard_NotOffered, CantAffordAtFiveLess_ModeNotOffered,
#//                 HandDiscardedCopy_AfterTheDefeatedCopyLeft_NotOffered (provenance),
#//                 PilotDefeatedAsAnUpgrade_IsNotAUnitThatWasDefeated (value class: a unit card as an upgrade)
#//           boundary=CostsFiveLess (9 = 8+1 plays) vs CantAffordAtFiveLess (8 does not); the zero floor in
#//                    Offer_* (two units costing ≤5 offered with 0 left); FiveHP_IsDefeated (5 HP at -5 dies)
#//           dispatch=ReplayedUnit_ItsOwnWhenPlayedFires (a full play, not a put-into-play) +
#//                    Exploit_AffordableOnlyByExploiting_OfferedAndPlayed (the pool counts Exploit);
#//                    DeployedEnemyLeader_EnteredThisPhase_Debuffed (leader unit value class)
#//           duration=EnteredPlayDebuff_ExpiresAtTheEndOfThePhase
#//           control=Control_YourUnitAnOpponentControlled_DefeatedIntoYourDiscard_Offered,
#//                   Control_AnOpponentsUnitYouControlled_GoesToTheirDiscard_NotOffered,
#//                   Control_EnteredThenChangedSides_StillEnteredThisPhase
#//           reqboundary=RequestBoundary_BeforeTheModeAndTheUnit
#//           modes=2P, TwinSuns (mode 2's "a unit" is the whole table: TwinSuns_FarSeatUnitThatEnteredThisPhase);
#//                 TeamSuns N/A — no "friendly"/"enemy" in the text, so a teammate is just another seat of the
#//                 same whole-table pool the Twin Suns section already walks. Mode 1 is "your discard": self only.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1SpaceArena: JTL_251:1:6
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>PlayHand:0
#// The only legal discard unit, so the pick resolves itself once the mode is chosen.
- P1>AnswerDecision:PlayFromDiscard

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_042
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_251
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:0
P1RESAVAILABLE:0
P1NODECISION

---

# CantAffordAtFiveLess_ModeNotOffered
#// The boundary partner of the section above: with 8 resources Obi-Wan leaves 0, and JTL_251 still costs 1 at
#// -5, so mode 1 can only fizzle and is not on the menu. The menu is mode 2 (Obi-Wan himself entered play) + Pass.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1SpaceArena: JTL_251:1:6
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1OPTIONNOT:PlayFromDiscard
P1OPTIONHAS:EnteredPlayDebuff
P1OPTIONHAS:Pass

---

# Offer_OnlyUnitsDefeatedThisPhase_FromYourDiscard
#// The pool is the units in YOUR discard that were defeated THIS phase. P1's discard holds a SEEDED SOR_046 (never
#// defeated — it stands in for a copy defeated in an earlier round) and, after two trades, a defeated SOR_046 and a
#// defeated TWI_057. Only the two defeated copies are offered — the seeded one shares a CardID with a defeated
#// copy, so a CardID-only "defeated this phase" read would wrongly offer it too. P2's TWI_057 also died this
#// phase, but it is in P2's discard: not yours.
#// 8 resources leave 0 after Obi-Wan, and both still offered: SOR_046 (4) and TWI_057 (1) are free at -5 (floor 0).

## GIVEN
CommonSetup: bbw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1Discard: SOR_046
WithP1GroundArena: SOR_046:1:6
WithP1GroundArena: TWI_057:1:3
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: TWI_057:1:3

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackGroundArena:0:1
- P1>PlayHand:0
- P1>AnswerDecision:PlayFromDiscard

## EXPECT
P1DISCARDCOUNT:3
P1DISCARDUNIT:0:CARDID:SOR_046
P1DISCARDUNIT:1:CARDID:SOR_046
P1DISCARDUNIT:2:CARDID:TWI_057
P2DISCARDCOUNT:1
P1SELECTABLEEXACT:myDiscard-1&myDiscard-2

---

# SeededDiscardUnit_NotDefeatedThisPhase_ModeNotOffered
#// A unit that is merely IN the discard (placed by the fixture, never defeated this phase) does not qualify, so
#// mode 1 has no legal unit and is left off the menu — even with resources to spare.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1Discard: JTL_251

## WHEN
- P1>PlayHand:0

## EXPECT
P1OPTIONNOT:PlayFromDiscard
P1OPTIONHAS:EnteredPlayDebuff
P1OPTIONHAS:Pass

---

# HandDiscardedCopy_AfterTheDefeatedCopyLeft_NotOffered
#// Provenance, not the CardID count. SOR_046 dies this phase (the "defeated" count for SOR_046 is now 1), SOR_091
#// The Emperor's Legion returns it to hand, and SOR_167 Force Throw makes P1 discard it FROM HAND. The SOR_046 now
#// in the discard was discarded, not defeated, so mode 1 has nothing — although the stale count still says 1.
#// Costs under bbw: Legion 2 + 4 off-aspect = 6, Force Throw 1 + 2 = 3, Obi-Wan 8 → 17. No Force unit is in play,
#// so Force Throw's damage rider never asks.

## GIVEN
CommonSetup: bbw/rrk/{myResources:17}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_091
WithP1Hand: SOR_167
WithP1Hand: IC27_042
WithP1GroundArena: SOR_046:1:6
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:You
- P1>AnswerDecision:myHand-1
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:3
P1DISCARDUNIT:2:CARDID:SOR_046
P1OPTIONNOT:PlayFromDiscard
P1OPTIONHAS:EnteredPlayDebuff

---

# PilotDefeatedAsAnUpgrade_IsNotAUnitThatWasDefeated
#// JTL_046 Paige Tico is a unit card, but here she is played as a Pilot UPGRADE on JTL_251. When the host dies,
#// Paige is defeated as an upgrade — she was not a unit when she was defeated, so she is not "a unit that was
#// defeated this phase" (the reading SOR_091 and TWI_189 share). With 8 resources JTL_251 (1 at -5) is
#// unaffordable and Paige would be free, so a pool that admitted her would put mode 1 on the menu.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1SpaceArena: JTL_251:1:6
WithP1SpaceArenaUpgrade: 0:JTL_046
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:2
P1OPTIONNOT:PlayFromDiscard
P1OPTIONHAS:EnteredPlayDebuff

---

# Exploit_AffordableOnlyByExploiting_OfferedAndPlayed
#// Mode 1 is a full play, which offers Exploit, so a unit affordable only by exploiting belongs in the pool.
#// TWI_037 Droideka Security (Exploit 2, Vigilance/Villainy): 6 + 2 off-aspect = 8, so 3 at -5 — with 0 resources
#// left after Obi-Wan it is affordable only by defeating two units. P1 exploits both SOR_046s: 3 - 4 → 0.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: TWI_037:1:4
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:2:0
- P1>PlayHand:0
- P1>AnswerDecision:PlayFromDiscard
- P1>AnswerDecision:myGroundArena-0&myGroundArena-1

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:IC27_042
P1GROUNDARENAUNIT:1:CARDID:TWI_037
P1DISCARDCOUNT:2
P1RESAVAILABLE:0

---

# EnemyUnitDefeatedThisPhase_InTheirDiscard_NotOffered
#// "From YOUR discard pile": P1 defeats P2's TWI_057 this phase, which goes to P2's discard. Nothing of P1's was
#// defeated, so mode 1 is not offered.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: TWI_057:1:3

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P2DISCARDCOUNT:1
P1DISCARDCOUNT:0
P1OPTIONNOT:PlayFromDiscard
P1OPTIONHAS:EnteredPlayDebuff

---

# ReplayedUnit_ItsOwnWhenPlayedFires
#// Mode 1 PLAYS the unit (the full play ceremony), so the replayed unit's own When Played fires: TWI_144 Batch
#// Brothers creates a Clone Trooper token. (Batch Brothers is Aggression/Heroism, so 3 + 2 off-aspect = 5, free at -5.)

## GIVEN
CommonSetup: bbw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1GroundArena: TWI_144:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:PlayFromDiscard

## EXPECT
P1DISCARDCOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:IC27_042
P1GROUNDARENAUNIT:1:CARDID:TWI_144
P1RESAVAILABLE:0

---

# Control_YourUnitAnOpponentControlled_DefeatedIntoYourDiscard_Offered
#// Owner ≠ controller. P2 controls a SOR_128 that P1 OWNS; P1 defeats it, and a defeated card goes to its OWNER's
#// discard — P1's. It was defeated this phase and is in your discard pile, so mode 1 plays it back for P1.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1GroundArena: SOR_046:1:0
WithP2GroundArenaControlled: SOR_128:1

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:PlayFromDiscard

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:0
P1DISCARDCOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:2:CARDID:SOR_128

---

# Control_AnOpponentsUnitYouControlled_GoesToTheirDiscard_NotOffered
#// The mirror image: P1 controls a SOR_128 that P2 OWNS, and it dies attacking. It goes to P2's discard, so it is
#// not in YOUR discard pile and mode 1 has nothing to offer.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1GroundArenaControlled: SOR_128:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:0
P2DISCARDCOUNT:1
P1OPTIONNOT:PlayFromDiscard

---

# EnteredPlayDebuff_Offer_EnteredThisPhaseOnly
#// Mode 2's pool: every unit that entered play this phase — P1's SOR_046 (played) and Obi-Wan himself, who has
#// entered by the time his When Played resolves. P2's SOR_046 was placed by the fixture and never entered, so it
#// is excluded.

## GIVEN
CommonSetup: bbw/rrk/{myResources:12}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_046
WithP1Hand: IC27_042
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:EnteredPlayDebuff

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# EnteredPlayDebuff_TheChosenUnitGetsMinusFiveMinusFive
#// SOR_046 (3/7) played this phase gets -5/-5: power floors at 0, HP 7 → 2. Obi-Wan is untouched. (HP:2 separates
#// -5 from a -4 misread, which would leave 3.)

## GIVEN
CommonSetup: bbw/rrk/{myResources:12}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_046
WithP1Hand: IC27_042

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:EnteredPlayDebuff
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HP:2
P1GROUNDARENAUNIT:1:CARDID:IC27_042
P1GROUNDARENAUNIT:1:POWER:5
P1GROUNDARENAUNIT:1:HP:9

---

# EnteredPlayDebuff_FiveHP_IsDefeated
#// The HP boundary: SHD_060 (2/5) played this phase drops to 0 HP and is defeated. A -4 misread would leave it at 1.

## GIVEN
CommonSetup: bbw/rrk/{myResources:11}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SHD_060
WithP1Hand: IC27_042

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:EnteredPlayDebuff
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_060

---

# EnteredPlayDebuff_ExpiresAtTheEndOfThePhase
#// "For this phase": once the action phase ends, SOR_046 is back to its printed 3/7.

## GIVEN
CommonSetup: bbw/rrk/{myResources:12}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_046
WithP1Hand: IC27_042
WithP1Deck: [SEC_080 SEC_080 SEC_080]
WithP2Deck: [SEC_080 SEC_080 SEC_080]

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:EnteredPlayDebuff
- P1>AnswerDecision:myGroundArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:7

---

# EnteredPlayDebuff_ObiWanCanChooseHimself
#// "Choose a unit that entered play this phase" — Obi-Wan himself qualifies. With nothing else entered he is the
#// only legal unit, so choosing mode 2 resolves onto him: 5/9 → 0/4.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EnteredPlayDebuff

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:IC27_042
P1GROUNDARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:HP:4
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7
P1NODECISION

---

# DeployedEnemyLeader_EnteredThisPhase_Debuffed
#// A leader that DEPLOYS enters play (it is not "played"), and mode 2 says "entered play", so an enemy leader
#// unit deployed this phase is a legal target. HMW_011 is 4/5, so -5/-5 defeats it.

## GIVEN
CommonSetup: bbw/grw/{theirLeader:HMW_011}
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 8
WithP2Resources: 6
WithP1Hand: IC27_042

## WHEN
- P2>DeployLeader
- P1>PlayHand:0
- P1>AnswerDecision:EnteredPlayDebuff
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2LEADER:NOTDEPLOYED
P1GROUNDARENAUNIT:0:POWER:5

---

# Control_EnteredThenChangedSides_StillEnteredThisPhase
#// A unit that entered play this phase still did after its control changes. P2 plays SOR_046; P1 takes it with
#// SOR_224 Change of Heart; then Obi-Wan's mode 2 offers it (now on P1's side) alongside Obi-Wan himself.
#// Change of Heart is Cunning: 6 + 2 off-aspect = 8, plus Obi-Wan's 8. P2 pays 4 + 2 off-aspect (grw has no Vigilance).

## GIVEN
CommonSetup: bbw/grw
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 16
WithP2Resources: 6
WithP1Hand: SOR_224
WithP1Hand: IC27_042
WithP2Hand: SOR_046

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:EnteredPlayDebuff

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# Decline_Pass_NothingHappens
#// "You MAY choose one": Pass with both modes available. JTL_251 stays in the discard, nothing is debuffed, and the
#// resource Obi-Wan left over is unspent.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1SpaceArena: JTL_251:1:6
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>PlayHand:0
- P1>AnswerDecision:Pass

## EXPECT
P1DISCARDCOUNT:1
P1SPACEARENACOUNT:0
P1RESAVAILABLE:1
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:9
P1NODECISION

---

# RequestBoundary_BeforeTheModeAndTheUnit
#// A request boundary before each answer. Two defeated units are in the discard so the unit pick stays pending:
#// TWI_144 (free at -5) and JTL_251 (1 at -5). Picking JTL_251 with exactly 1 resource left only works if the -5
#// survived both boundaries.

## GIVEN
CommonSetup: bbw/rrk/{myResources:9}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: IC27_042
WithP1GroundArena: TWI_144:1:0
WithP1SpaceArena: JTL_251:1:6
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AttackSpaceArena:0:0
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:PlayFromDiscard
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myDiscard-1

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:JTL_251
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:TWI_144
P1RESAVAILABLE:0

---

# TwinSuns_FarSeatUnitThatEnteredThisPhase
#// Three seats, no teams. Mode 2's "a unit" is the whole table: seat 3 plays TWI_057 this phase, and Obi-Wan's
#// -5/-5 defeats it (1/4). A pool built from "me + the other player" would never reach seat 3.

## GIVEN
CommonSetup: bbw/rrk/{myResources:8}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 3
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP3Resources: 4
WithP3Hand: TWI_057
WithP1Hand: IC27_042

## WHEN
- P3>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:EnteredPlayDebuff
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:3
P3GROUNDARENACOUNT:0
P3DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:POWER:5
