# Gate_TatooineBase_ReturnsAnEnemyUpgrade
#// COVERAGE: offer=Offer_CostThreeIsInCostFourIsOut (SELECTABLEEXACT; the boundary pair lives in the
#//           same pool, and a friendly upgrade sits in it beside an enemy one)
#//           decline=Decline_NothingIsReturned (+ NoLegalUpgrade_GateOpenButNoPrompt — a fizzle-only
#//           optional is never offered, a different branch from declining)
#//           boundary=Offer_CostThreeIsInCostFourIsOut (cost 3 in / cost 4 out)
#//           control=ReturnsToTheOwnersHand_NotTheHostControllers — the upgrade is OWNED by P1 and sits
#//           on a unit CONTROLLED by P2, which is the only board where "its owner's hand" and "the
#//           host's controller's hand" name different players
#//           reqboundary=AcrossTheRequestBoundary
#//
#// HMW_222 Sandcrawler Sales Team — Unit (Ground) 3/2, cost 2, [Cunning], Jawa, non-unique.
#// "Saboteur (When this unit attacks, ignore Sentinel and defeat the defender's Shields.)
#//  When Played: If you control a Tatooine base, you may return an upgrade that costs 3 or less to its
#//  owner's hand."
#//
#// Two OFFICIAL rulings (Pre Vizsla - Power Hungry, card-specific-rulings.md) settle what "an upgrade
#// that costs 3 or less" means, and both cut against the obvious implementation:
#//   • "Abilities that refer to a card's cost always refer to its PRINTED cost, regardless of
#//     modifiers." — so a discount or an alternate (Piloting) cost never changes eligibility.
#//   • "TOKEN UPGRADES ARE CONSIDERED UPGRADES." — a Shield or Experience token costs 0 and is a legal
#//     target. See TokenUpgradeIsALegalTarget_ButCeasesInsteadOfGoingToHand.
#//
#// Base is the Tatooine SHD_026 Jabba's Palace (Cunning, 30 HP, blank text) so it changes nothing but
#// the trait. SOR_120 Academy Training is a blank-text +2/+2 costing 2.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_222
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_222
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1
P1HANDCOUNT:0

---

# Gate_NonTatooineBase_NoPrompt
#// THE GATE NEGATIVE. Identical board, except the base is the CommonSetup default SOR_029
#// Administrator's Tower (Cloud City). The upgrade must be left alone and no prompt may appear.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_222
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2HANDCOUNT:0
P1NODECISION

---

# Gate_OpponentsTatooineBaseDoesNotCount
#// "If YOU CONTROL a Tatooine base" — the opponent's Tatooine base is not yours. A base lookup that
#// reads the wrong seat, or one that scans every base in the game, opens the gate here.

## GIVEN
CommonSetup: yyk/yyk/{theirBase:SHD_026;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_222
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# Offer_CostThreeIsInCostFourIsOut
#// ⚠ THE OFFER AND THE BOUNDARY PAIR IN ONE POOL. Answering a target proves the branch, never which
#// upgrades were eligible, and a lone "cost 4 was blocked" proves nothing without "cost 3 worked".
#//   myGroundArena-0.u0   SOR_069 Resilient              cost 1 → OFFERED (a FRIENDLY upgrade: the text
#//                                                                says "an upgrade", with no side)
#//   theirGroundArena-0.u0 ASH_087 Cybernetic Enhancements cost 3 → OFFERED (the boundary, inclusive)
#//   theirGroundArena-0.u1 LAW_129 Mastery                cost 4 → EXCLUDED (one over)
#// Both enemy upgrades sit on the SAME host, so host choice cannot explain the difference — only cost.
#// Both are seeded rather than played, so neither When Played fires.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_222
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:ASH_087
WithP2GroundArenaUpgrade: 0:LAW_129

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0.u0&theirGroundArena-0.u0

---

# ReturnsToTheOwnersHand_NotTheHostControllers
#// ⚠ THE OWNERSHIP CELL. "to its OWNER's hand" — not the hand of whoever controls the unit it is
#// attached to. P1 plays Academy Training (blank text, no printed controller restriction, so CR 2.e
#// makes it enemy-attachable) onto P2's unit, then plays Sandcrawler Sales Team and returns it.
#// It must come back to P1's hand. An implementation that returns it to the HOST's controller — the
#// natural thing to write, since the host is what you have in hand at that point — sends it to P2 and
#// every other section in this file still passes, because everywhere else owner and controller agree.
#// Costs: Academy Training is [Command] against a Cunning base + Cunning/Villainy leader, so it pays
#// its printed 2 plus a +2 off-aspect penalty = 4; Sandcrawler is on-aspect at 2. Hence 6 resources.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:6}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: [SOR_120 HMW_222]
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1HANDCOUNT:1
P1HANDCARD:0:SOR_120
P2HANDCOUNT:0

---

# TokenUpgradeIsALegalTarget_ButCeasesInsteadOfGoingToHand
#// ⚠ OFFICIAL RULING (Pre Vizsla): "Token upgrades are considered upgrades." A Shield token costs 0, so
#// it passes "costs 3 or less" and MUST be offered — the instinct to filter tokens out of an
#// upgrade pool is wrong here (LAW_224 Liberty does filter them, which is worth a second look).
#// But returning one to its owner's hand does NOT put a card in hand: a token ceases to exist when it
#// leaves play (CR 5.8). So the Shield disappears and NOBODY's hand grows — asserting both halves is
#// what separates "handled correctly" from "quietly created a phantom card in hand".

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_222
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:0
P1HANDCOUNT:0

---

# FortifyUpgradeOnABaseIsALegalTarget
#// A Fortify upgrade is attached to a BASE rather than a unit, so a collector that only walks the four
#// arenas never sees it. HMW_205 Intelligence Agency costs 1, which is comfortably inside the
#// threshold, and it is a real (non-token) upgrade so it genuinely returns to its owner's hand.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_222
WithP2BaseUpgrade: HMW_205

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0.u0

## EXPECT
P2BASE:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# Decline_NothingIsReturned
#// "You MAY return" — the decline branch, with two legal upgrades so the choice is genuinely open.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_222
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_069
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:0
P2HANDCOUNT:0
P1NODECISION

---

# NoLegalUpgrade_GateOpenButNoPrompt
#// A DIFFERENT branch from declining: the gate is open, but the only upgrade in play costs 4, so there
#// is nothing the effect could do and no prompt may be raised at all. The end state is identical to a
#// decline, so P1NODECISION is the only assertion that separates them.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_222
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:LAW_129

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# NoUpgradeInPlay_GateOpenButNoPrompt
#// The empty-board form of the same rule — nothing to return anywhere. Guards against a collector that
#// raises a prompt over an empty pool.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_222
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1NODECISION

---

# Saboteur_IgnoresSentinelAndDefeatsTheDefendersShields
#// The card's OTHER printed clause. Saboteur is auto-derived into the generated $Saboteur_Cards
#// registry and dispatched generically, so this is a membership guard rather than new behaviour — but
#// it is a clause, and a registry miss would silently drop half the card.
#// Both halves in one attack: SOR_063 Cloud City Wing Guard has Sentinel and would normally FORCE
#// itself as the defender, and the chosen defender carries a Shield that must be defeated before
#// damage. Sandcrawler is 3/2 and SOR_046 is 3/7, so they trade damage and Sandcrawler dies — its
#// 3 damage still lands because the Shield is gone.

## GIVEN
CommonSetup: yyk/yyk/{myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_222:1:0
WithP2GroundArena: [SOR_063:1:0 SOR_046:1:0]
WithP2GroundArenaUpgrade: 1:SOR_T02

## WHEN
- P1>AttackGroundArena:0:1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SOR_046
P2GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:0

---

# AcrossTheRequestBoundary
#// THE REQUEST-BOUNDARY CELL. The upgrade pick is interactive, so in production it ends the request and
#// the continuation that resolves the subcard mzID and bounces it resumes in a FRESH process. Anything
#// held in an in-memory global between building the pool and applying the return — the gate result, the
#// host, the chosen subcard — is gone by then and the upgrade silently stays attached.
#// Same board and answer as the opening section, with one boundary inserted.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:3}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: HMW_222
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0.u0

## EXPECT
P2GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2HANDCOUNT:1

---

# TwinSuns_OfferContainsTheFarSeatsUpgrade
#// The POOL half of the seat-count cell, and deliberately also the SEED GUARD for the section below.
#// ⚠ WithP3GroundArenaUpgrade was accepted by the runner but silently DROPPED by the builder until
#// 2026-08-24 — the request was filed and never resolved onto the unit, so seat 3's unit had empty
#// Subcards and any assertion of the form "the far-seat upgrade is gone" passed without the upgrade
#// ever having existed. A SELECTABLEEXACT cannot fail that way: with nothing seeded there is no pending
#// decision at all and the assertion errors out loudly.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1Hand: HMW_222
WithP3GroundArena: SOR_046:1:0
WithP3GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:p3GroundArena-0.u0

---

# TwinSuns_ReturnsAFarSeatsUpgrade
#// ⚠ THE SEAT-COUNT CELL. "An upgrade" names no player, so the pool spans every live seat — and at four
#// seats the far seat is addressed positionally (p3GroundArena-0.u0), an mzID that does not exist at
#// two seats, so this section cannot pass there.
#// Seat 3's upgrade is the ONLY one in the game, which means a pool truncated to seats 1-2 would find
#// nothing at all and raise no prompt. It returns to SEAT 3's hand, since seat 3 owns it.

## GIVEN
CommonSetup: yyk/yyk/{myBase:SHD_026;myResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1Hand: HMW_222
WithP3GroundArena: SOR_046:1:0
WithP3GroundArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0.u0

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:UPGRADECOUNT:0
P3HANDCOUNT:1
P1HANDCOUNT:0
