# DefeatsAUnitThatAttackedYourBase
#// COVERAGE: offer=OfferPool_OnlyBaseAttackersAreSelectable (P1SELECTABLEEXACT over a board with two
#//           qualifying attackers, one enemy non-attacker, and a FRIENDLY unit that attacked the OTHER
#//           base — N+1 fixtures so the pool survives a legal target auto-resolving)
#//           decline=Decline_NothingIsDefeated ("you may"); the no-target case is
#//           NoBaseAttackers_NoPromptAtAll
#//           boundary=N/A — STRUCTURAL: no numeric threshold anywhere in the text. The only branch is
#//           the leader-unit rider, which is a KIND, not a quantity, and is covered as a pair by
#//           LeaderUnitTarget_QuiGonDefeatsHimself / NonLeaderTarget_QuiGonSURVIVES.
#//           control=ControlChanged_TheMarkerStillAnswers (the pool is unqualified — "a unit" — so a
#//           base-attacker you have since TAKEN CONTROL OF is still a legal target, and the marker has
#//           to survive that)
#//           reqboundary=SurvivesTheRequestBoundary
#//           modes=2P,TwinSuns — "your base" is a DETERMINED seat (the caster's), never a prompt, but
#//           above two seats there are several bases and a unit that attacked someone ELSE'S must not
#//           qualify: TwinSuns_AUnitThatAttackedANOTHERSeatsBaseIsNotATarget.
#//           TeamSuns=N/A — "your base" is your own in a team game too; a teammate has their own base,
#//           so the team axis adds no distinct code path.
#//
#// HMW_078 Qui-Gon Jinn, We'll Handle This — 5-cost 2/5 Ground, Force·Jedi·Republic, unique.
#//   "Grit
#//    When Played: You may defeat a unit that attacked your base this phase. If it's a leader unit,
#//    defeat this unit."
#// ⚠ PREVIEW SET — no official rulings exist for HMW. The clause "attacked your base this phase" is
#// however word-for-word SHD_088 Ephant Mon and SHD_106 Rule with Respect, both released and both
#// already implemented against the same per-unit phase marker — so this follows established precedent
#// rather than a guess.
#// USER RULING (2026-09-01) - THREE THINGS COUNT AS "A UNIT DEALING DAMAGE TO A BASE", AND QUI-GON
#// FIRES ON ONLY THE FIRST: (1) an ATTACK on the base, (2) a unit ABILITY that pings the base (SOR_014
#// Sabine Wren's deployed On Attack), and (3) OVERWHELM excess spilling to the base. A card like
#// SEC_136 Retaliation counts all three; this one says "ATTACKED your base", so only (1) qualifies.
#// That distinction is the whole reason this card cannot reuse the engine's existing base markers,
#// which record DAMAGE and are set by all three routes. Guarded by
#// OverwhelmSpillToTheBaseIsNOTAnAttack and AbilityPingOnYourBaseIsNOTAnAttack.
#//
#// ⚠ Note what the text does NOT say. It says "a unit", not "an enemy unit" and not "a non-leader
#// unit" — unlike BOTH of its released twins, which say "enemy non-leader unit". The leader case is
#// not merely allowed here, it is the whole point of the second sentence.
#//
#// THE POSITIVE. P2's Dark Trooper attacks P1's base, then P1 plays Qui-Gon and defeats it. The base
#// damage (3) is asserted too, so the section fails loudly if the attack itself never happened — the
#// premise of every section in this file.

## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 5
WithP1Hand: [HMW_078]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1BASEDMG:3
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_078

---

# AUnitThatAttackedYourUNITSIsNotATarget
#// THE NEGATIVE THAT PINS THE WORD "BASE", and the one most likely to be got wrong: the engine already
#// carries a per-unit "attacked this phase" marker that says nothing about WHAT was attacked, and
#// reaching for that would pass every positive section in this file.
#// P2's Dark Trooper attacks a P1 UNIT instead of the base. It has attacked — but not P1's base — so
#// Qui-Gon has no legal target and no prompt is raised at all.
#// ⚠ THE ATTACKER MUST SURVIVE THE TRADE, or it is not a live candidate and the exclusion is untested:
#// a dead unit is out of the pool for a reason that has nothing to do with what it attacked. SOR_063
#// Cloud City Wing Guard is a 2/4, so it eats the 3 and counters for only 2 — leaving the 3/3 Dark
#// Trooper standing on 2 damage, which is exactly the board this section needs.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 5
WithP1Hand: [HMW_078]
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P1BASEDMG:0
P1NODECISION
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:2

---

# AUnitThatDidNotAttackAtAllIsNotATarget
#// The plainest negative: an enemy unit that simply sat there. Without it, "the pool is every enemy
#// unit" would satisfy the positive section above.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: [HMW_078]
WithP2GroundArena: SEC_080:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P2GROUNDARENACOUNT:1
P1GROUNDARENACOUNT:1

---

# OfferPool_OnlyBaseAttackersAreSelectable
#// THE OFFER CELL — answering a target proves the branch, never the pool. The decision is left PENDING
#// and the pool itself asserted.
#// The board separates all four populations at once: TWO enemy units that attacked P1's base (both
#// legal, so nothing auto-resolves), ONE enemy unit that never attacked (excluded), and a FRIENDLY
#// unit that attacked P2's base — excluded not because it is friendly (the text says "a unit", with no
#// controller word) but because the base it attacked was not P1's. A pool keyed on "attacked anything"
#// or on "is an enemy" admits that friendly attacker; a pool keyed on the enemy side alone would still
#// admit the non-attacker.
#// ⚠ Two copies of one CardID need SEPARATE WithP2GroundArena lines.
#// ⚠ P1's filler attack is also what lets P2 act twice — the turn alternates, so P1 must spend an
#// action between P2's two attacks.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 5
WithP1Hand: [HMW_078]
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE
- P2>AttackGroundArena:1:BASE
- P1>PlayHand:0
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# Decline_NothingIsDefeated
#// THE DECLINE BRANCH. "You MAY defeat" — refusing must leave the attacker alive, and must not defeat
#// Qui-Gon either (the leader rider hangs off a defeat that never happened).
#// ⚠ A "may" target does NOT auto-resolve even with a single legal target, so the decline is a real
#// answer (`-`) rather than an absent prompt.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 5
WithP1Hand: [HMW_078]
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_078
P1NODECISION

---

# NoBaseAttackers_NoPromptAtAll
#// NO VALID TARGET. An empty enemy board — Qui-Gon still enters play and the card still costs its 5,
#// but nothing is offered and no decision is left dangling. Distinct from the decline above: there the
#// player chose not to; here there was never a choice.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1Hand: [HMW_078]
## WHEN
- P1>PlayHand:0
## EXPECT
P1NODECISION
P2NODECISION
P1GROUNDARENACOUNT:1
P1RESAVAILABLE:0

---

# LeaderUnitTarget_QuiGonDefeatsHimself
#// CLAUSE 3, THE RIDER. "If it's a leader unit, defeat this unit" — the price of using this on a
#// deployed leader is Qui-Gon himself.
#// P2's deployed leader attacks P1's base, and Qui-Gon defeats it. A defeated LEADER unit returns to
#// its leader zone rather than to a discard pile, so P2's arena empties and its leader reads
#// NOTDEPLOYED — while Qui-Gon, an ordinary unit, goes to P1's discard.
#// The two halves are asserted separately so a rider that fired on the wrong subject is visible.
#// WARN THE LEADER IS CHOSEN FOR HAVING NO DEPLOYED "On Attack". The default Villainy leader here is
#// SOR_010 Darth Vader, whose deployed side is "On Attack: you may deal 2 damage to a unit" - that
#// prompt PAUSES combat, so the base damage never lands and the whole section reads as the attack
#// silently not happening. SHD_007 Moff Gideon (3/6) has only Overwhelm and a passive that applies
#// while attacking a UNIT, so a base attack is clean.
## GIVEN
CommonSetup: bbw/rrk/{theirLeader:SHD_007;theirLeaderDeployed:true}
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 5
WithP1Hand: [HMW_078]
## WHEN
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:3
P2GROUNDARENACOUNT:0
P2LEADER:NOTDEPLOYED
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1

---

# NonLeaderTarget_QuiGonSURVIVES
#// THE OTHER HALF OF THE PAIR, and the one that proves the rider is CONDITIONAL rather than a flat
#// "defeat this unit". Identical flow against an ordinary unit: the attacker dies, Qui-Gon lives.
#// Without this section a rider that always self-defeated would pass the leader section above.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 5
WithP1Hand: [HMW_078]
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_078
P1DISCARDCOUNT:0

---

# Grit_PowerRisesWithDamage
#// CLAUSE 1. Grit is keyword-only and already auto-wired from the generated registry, so this is a
#// verification section rather than new behaviour — but a keyword-plus-rider card that ships with only
#// the rider tested is exactly how half a card goes uncovered.
#// Grit is +1/+0 per damage (power only, NOT +1/+1), so a 2/5 Qui-Gon on 3 damage is a 5/5.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: HMW_078:1:3
## WHEN
- P1>Drain
## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# ControlChanged_TheMarkerStillAnswers
#// THE CONTROL-CHANGE CELL, and it is the reason the marker is stored the way it is.
#// The text says "a unit" with NO controller word, so a unit that attacked your base and which you have
#// since TAKEN CONTROL OF is still a legal target — an unusual play, but a legal one, and the rules
#// give no reason to exclude it.
#// It is also the load-bearing test of the marker's DESIGN. The engine's existing base-attack markers
#// are stored in the ATTACKER'S CONTROLLER's namespace, and nothing migrates them when control moves —
#// so a marker read that way answers "no" here and the unit silently drops out of the pool. Storing it
#// against the ATTACKED BASE'S OWNER instead makes the answer independent of who controls the unit
#// now, which is what this section pins.
#// P2's Dark Trooper attacks P1's base; P1 plays SOR_224 Change of Heart to take it; P1 then plays
#// Qui-Gon and defeats the unit it now controls. Its OWNER is P2, so it goes to P2's discard.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithActivePlayer: 2
#// ⚠ SOR_224 Change of Heart is CUNNING and cost 6; under this Vigilance base/leader the pip is
#// uncovered, so it costs 8, and Qui-Gon another 5 — 13 in all. Underpaying here does not error,
#// it silently leaves the card in hand and the whole premise never happens.
WithP1Resources: 13
WithP1Hand: [SOR_224 HMW_078]
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
- P1>PlayHand:0
#// WARN Every action swaps the turn, so P2 has to spend one before P1 can play again. Without this the
#// second play runs out of turn, is refused, and the section reads as Qui-Gon doing nothing.
- P2>Pass
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:HMW_078

---

# SurvivesTheRequestBoundary
#// THE REQUEST-BOUNDARY CELL. The marker is written by ONE player's action (P2's attack) and read by a
#// DIFFERENT player's action (P1's play) — in production two separate requests in two separate
#// processes, with a gamestate write and re-parse in between. A marker held anywhere but in serialized
#// state is empty by the time Qui-Gon looks, and the failure mode is silent: no prompt, as though
#// nothing had ever attacked.
#// Same board and same expected outcome as the base case, with the boundary inserted between the two.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 5
WithP1Hand: [HMW_078]
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1BASEDMG:3
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1

---

# TwinSuns_AUnitThatAttackedANOTHERSeatsBaseIsNotATarget
#// TWIN SUNS — CANNOT PASS AT TWO SEATS. "Your base" is a DETERMINED seat, so above two seats a unit
#// that attacked someone ELSE'S base has not attacked yours and must not be offered.
#// Seat 2's unit attacks SEAT 3's base. Seat 1 then plays Qui-Gon and must see no legal target at all.
#// This is precisely the failure the engine's owner-qualified base marker was added to fix: a marker
#// that records only "attacked a base" — or one that derives the victim with OtherPlayer() — answers
#// "yes" here and hands seat 1 a free defeat of a unit that never touched it.
#// ⚠ CommonSetup dresses seats 1-2 only, so seat 3 needs its own base line.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1Resources: 5
WithP1Hand: [HMW_078]
WithP2GroundArena: [SEC_080:1:0]
## WHEN
#// ⚠ At four seats the turn order is 1->2->3->4, so seat 1 does NOT act straight after seat 2 —
#// seats 3 and 4 have to pass first. Omitting them leaves the play silently unexecuted, which reads
#// exactly like the card fizzling.
- P2>AttackGroundArena:0:p3Base-0
- P3>Pass
- P4>Pass
- P1>PlayHand:0
## EXPECT
SEATCOUNT:4
P3BASEDMG:3
P1BASEDMG:0
P1NODECISION
P1GROUNDARENACOUNT:1

---

# PhaseScoped_MarkerClearsAtRegroup
#// THE DURATION EDGE. "This phase" must actually END. P2 attacks P1's base, both players pass to reach
#// the regroup, and in the NEXT action phase Qui-Gon finds nothing to defeat — the attacker is still
#// standing but its mark is gone.
#// A marker that is set and never cleared passes every positive section in this file forever.
#// ⚠ Both players need seeded decks: passing to regroup with an empty deck puts deck-out damage on the
#// base, which would move P1BASEDMG out from under the assertion below.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 5
WithP1Hand: [HMW_078]
WithP1Deck: [SOR_095 SOR_095 SOR_095 SOR_095 SOR_095 SOR_095]
WithP2Deck: [SEC_080 SEC_080 SEC_080 SEC_080 SEC_080 SEC_080]
WithP2GroundArena: SEC_080:1:0
## WHEN
- P2>AttackGroundArena:0:BASE
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass
- P1>PlayHand:0
## EXPECT
P1BASEDMG:3
P2GROUNDARENACOUNT:1
P1GROUNDARENACOUNT:1
P1NODECISION

---

# OverwhelmSpillToTheBaseIsNOTAnAttack
#// USER RULING, case 3. Overwhelm excess IS that unit damaging your base - but it is not that unit
#// ATTACKING your base, and Qui-Gon's clause says "attacked".
#// SOR_232 AT-ST (6/7, Overwhelm) attacks P1's 3/1 Stormtrooper: it kills it and 5 excess spills onto
#// P1's base, while the 3-power counter leaves the AT-ST alive on 3 damage. So P1's base IS damaged by
#// that exact unit, the unit is still standing and still a live candidate - and it must NOT be offered.
#// This is the section that separates "attacked" from "dealt damage", a distinction the engine's
#// pre-existing base markers deliberately blur for SHD_088 / SHD_106 / SEC_136. Reusing one of those
#// markers here is the obvious implementation, and this is the only thing that catches it.
## GIVEN
CommonSetup: bbw/rrk
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 5
WithP1Hand: [HMW_078]
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_232:1:0
## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P1BASEDMG:5
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# AbilityPingOnYourBaseIsNOTAnAttack
#// USER RULING, case 2. A unit ABILITY that pings your base is that unit damaging your base, and again
#// it is not that unit attacking it.
#// P2's deployed SOR_014 Sabine Wren (2/5) attacks P1's Consular Security Force - a UNIT - and her
#// deployed "On Attack: deal 1 damage to each enemy base" puts 1 on P1's base along the way. She
#// survives the 3-power counter, so she is a live candidate whose only connection to P1's base is that
#// ability. She must not be offered.
#// Distinct from the Overwhelm section above because the damage takes a completely different ROUTE:
#// Overwhelm spills out of the combat step, this one is an ability calling the base-damage funnel
#// directly. An implementation could plausibly catch one and not the other, so both are written.
## GIVEN
CommonSetup: bbw/rrk/{theirLeader:SOR_014;theirLeaderDeployed:true}
SkipPreGame: true
WithActivePlayer: 2
WithP1Resources: 5
WithP1Hand: [HMW_078]
WithP1GroundArena: SOR_046:1:0
## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0
- P1>PlayHand:0
## EXPECT
P1BASEDMG:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
