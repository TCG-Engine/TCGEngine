# EachPlayerChooses_BothChosenUnitsTake3
#// COVERAGE: offer=Offer_ContainsOnlyYourOwnUnits · decline=N/A (no "may"/"up to" anywhere — the choose is
#//           MANDATORY; the nearest branch is a seat with nothing to choose, see NoUnits_SeatSkippedSilently)
#//           boundary=N/A (no threshold — a flat 3 to each chosen unit; the numeric edge that IS present
#//                    is lethal-vs-survives, covered by BothChosenUnitsDie_BothDefeated + the 6/6 self case)
#//           control=ControlChange_StolenUnitSitsInTheThiefsPool_NotTheOwners
#//           reqboundary=RequestBoundary_ChainSurvivesBetweenSeats
#//           modes=2P,TwinSuns ("EACH PLAYER" is a LOOP over every live seat — TwinSuns_EverySeatChoosesAndTakes3
#//                 cannot pass at two seats) · TeamSuns=N/A ("a unit THEY CONTROL" is control-scoped, never
#//                 team-scoped, so a teammate's unit is not in your pool — same code path as Twin Suns)
#//
#// HMW_263 Wrecker, Wrecking the Empire (6 cost, 6/6, Heroism, Clone)
#//   "When Played: Each player chooses a unit they control. Deal 3 damage to each chosen unit."
#//
#// The baseline: P1 plays Wrecker, each player picks one of their own, both picks take 3.
#// ⚠ Wrecker is ALREADY IN PLAY when its own When Played resolves, so P1's pool is [SOR_046, Wrecker].

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Drain
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:HMW_263
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# CasterMayChooseWreckerItself
#// "A unit they control" carries no "another" and no "other" — and Wrecker has already entered play by
#// the time its When Played resolves, so it is a legal choice for its own controller. A 6/6 shrugs off 3.
#// This is the section that would red if the pool were built BEFORE the unit entered, or if it
#// self-excluded by UniqueID out of habit.

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-1
- P2>Drain
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:CARDID:HMW_263
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Offer_ContainsOnlyYourOwnUnits
#// ⚠ THE OFFER CELL. "A unit THEY CONTROL" scopes each seat's pool to its OWN board, so P1's offer must
#// contain P1's two units and NEITHER of P2's — answering a target could never reveal that.
#// The board deliberately gives P2 two units, so a pool built from the whole table would be visibly
#// four entries wide rather than two.

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# DeployedLeaderUnitIsALegalChoice
#// ⚠ The text says "a unit", NOT "a non-leader unit" — contrast TWI_238 Merciless Contest, one sentence
#// away in the same family, which DOES print "non-leader". A deployed leader unit must therefore be
#// offerable and damageable. P2's only unit is its deployed leader, so P2's pick auto-resolves onto it.

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P2LEADER:DEPLOYED

---

# NoUnits_SeatSkippedSilently
#// A seat that controls nothing has nothing to choose, so it is skipped without a prompt — the effect is
#// not blocked waiting on it. P2 has an empty board; P1's pick still resolves and takes its 3.
#// ⚠ P2NODECISION is the assertion that matters: a chain that queued an empty MZCHOOSE for P2 would
#// either stall or auto-resolve into a stale slot.

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:HMW_263
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENACOUNT:0
P1NODECISION
P2NODECISION

---

# LoneUnit_AutoResolvesWithNoPrompt
#// A mandatory single-target choose auto-resolves (PASSPARAMETER), so a seat whose only unit is the
#// forced pick is never prompted. P2 holds exactly one unit → no P2 prompt, and it still takes 3.
#// ⚠ P1 has TWO units (Wrecker + SOR_046) so P1 IS prompted — the contrast within one section is what
#// proves the auto-resolve is about pool size, not about the seat.

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Drain

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
P2NODECISION

---

# UnchosenUnitsAreUntouched
#// ⚠ THE NEGATIVE. "Deal 3 damage to each CHOSEN unit" — not to each unit, and (contrast LOF_177 Time of
#// Crisis, the same sentence inverted) not to each unit NOT chosen. Every seat here has a second unit
#// that nobody picked, and both must end on DAMAGE:0.

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Drain
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:2:CARDID:HMW_263
P1GROUNDARENAUNIT:2:DAMAGE:0
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:DAMAGE:0

---

# ShieldPreventsTheThreeDamage
#// Interaction with the standard modifiers: a Shield token on a chosen unit prevents the whole 3 and is
#// consumed. Proves the damage routes through the normal ability funnel's prevention chain rather than
#// writing ->Damage directly.

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Drain
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# BothChosenUnitsDie_BothDefeated
#// The lethal case, and the one that punishes a stale-mzID implementation: both chosen units are 3/1
#// glass cannons, so the first defeat COMPACTS its arena before the second unit is damaged. Resolving
#// each chosen unit by UniqueID at damage time is what keeps the second hit on target.
#// Each player's own defeated unit goes to THEIR discard.

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263}
P1OnlyActions: true
WithP1GroundArena: LAW_180:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Drain

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_263
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:LAW_180
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_128

---

# ControlChange_StolenUnitSitsInTheThiefsPool_NotTheOwners
#// ⚠ "A unit they CONTROL" — not own. P2 first steals P1's SOR_046 with Change of Heart (SOR_224), so
#// when Wrecker resolves that unit belongs to P2's pool and is absent from P1's.
#// The offer assertion is the whole point: P1 is left choosing between its remaining unit and Wrecker,
#// while the stolen SOR_046 is only reachable by P2.

## GIVEN
CommonSetup: ggw/yyk/{myResources:8;handCardIds:HMW_263;theirResources:6;theirhandCardIds:SOR_224}
WithActivePlayer: 2
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1

---

# TwinSuns_EverySeatChoosesAndTakes3
#// ⚠ CANNOT PASS AT TWO SEATS. "EACH PLAYER" is a LOOP over every LIVE seat, caster included — not
#// "you and an opponent". At four seats a two-seat chain would never ask seats 3 and 4, and their units
#// would take nothing. Seats 3 and 4 each hold exactly one unit so their picks auto-resolve; the
#// assertion that both took 3 is unreachable from any OtherPlayer()-shaped implementation.
#// ⚠ Every far-seat fixture must SURVIVE its 3 (LAW_124 4/7, SOR_046 3/7) — a 3/3 filler is
#// defeated by it and the arena reads empty, which looks exactly like the seat never being asked.

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263}
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Base: SOR_019
WithP4Base: SOR_019
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0
WithP3GroundArena: LAW_124:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Drain
- P3>Drain
- P4>Drain

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:CARDID:LAW_124
P2GROUNDARENAUNIT:0:DAMAGE:3
P3GROUNDARENAUNIT:0:CARDID:LAW_124
P3GROUNDARENAUNIT:0:DAMAGE:3
P4GROUNDARENAUNIT:0:CARDID:SOR_046
P4GROUNDARENAUNIT:0:DAMAGE:3

---

# RequestBoundary_ChainSurvivesBetweenSeats
#// ⚠ THE REQUEST-BOUNDARY CELL. The pick chain spans TWO players' answers, which in production means two
#// separate requests in two fresh processes — so the UIDs chosen so far, the caster and the remaining
#// seats must all ride the CUSTOM's own Param. An in-memory accumulator would be empty by the time P2
#// answered and P1's chosen unit would silently take nothing.
#// Byte-identical to the opening positive apart from the boundary inserted between the two picks.

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P2>Drain
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:HMW_263
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# SimultaneousDefeat_ObserverThatDiesInTheSameBatchStillFires
#// ⚠ THE SIMULTANEITY CELL, and the only one that can see it. "Deal 3 damage to each chosen unit" is ONE
#// ability dealing damage simultaneously (cf. the official Rancor Keeper ruling, 07/21/2026), so two
#// chosen units that die here die in the SAME batch — and a "when an enemy unit is defeated" observer
#// that is itself one of the victims must still see its co-victim.
#// P1 pre-damages Gideon Hask (SOR_036, 5/5 at 2 damage → 3 is exactly lethal) and CHOOSES HIM, so he is
#// resolved FIRST and is already dead when P2's SOR_128 (3/1) is defeated a moment later. His trigger
#// must still fire and put an Experience token on P1's only surviving friendly, Wrecker itself.
#// ⚠ The ORDER is load-bearing: seats are asked caster-first, so choosing the observer on the CASTER's
#// side is what puts it in the grave before the defeat it has to observe. Swap the sides and the section
#// passes without the batch window, testing nothing.

## GIVEN
CommonSetup: ggw/grk/{myResources:8;handCardIds:HMW_263}
P1OnlyActions: true
WithP1GroundArena: SOR_036:1:2
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Drain
- P1>Drain

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_263
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_T01
P2GROUNDARENACOUNT:0
