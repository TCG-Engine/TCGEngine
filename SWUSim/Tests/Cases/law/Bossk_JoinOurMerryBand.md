# OnAttackBuffDebuff
#// LAW_031 Bossk (3/5) — On Attack: give a unit +1/+1 for this phase; you may give a unit -1/-1 for this
#// phase. Bossk attacks the base; buff Bossk (+1/+1 -> 4/6), debuff enemy SOR_046 (-1/-1 -> 2/6).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_031:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_031
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6

---

# OnAttackBuffDebuff_SurvivesTheRequestBoundary
#// LAW_031 Bossk — the On Attack ability spans TWO interactive decisions (the mandatory +1/+1 pick, then
#// the optional -1/-1 pick), and in production every answer arrives in a fresh process. The already-applied
#// +1/+1 phase effect, the in-flight attack and the second pending offer therefore all have to be re-read
#// from the serialized gamestate. Mirrors OnAttackBuffDebuff with a request boundary inserted between the
#// two answers — the richer insertion point, because Bossk has already recorded the buff by then.
#// The second pick is a genuine MZMAYCHOOSE over two candidates (myGroundArena-0 & theirGroundArena-0).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_031:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_031
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:6
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:HP:6

---

# OnAttackBuffOfferIsEveryUnitInPlayInBothArenas
#// COVERAGE: (the file's first section is pre-existing and off-limits, so the ledger lives here.)
#//           offer=OnAttackBuffOfferIsEveryUnitInPlayInBothArenas (the +1/+1 pick) +
#//           OnAttackDebuffOfferIsAlsoEveryUnitInPlay_IncludingTheJustBuffedOne (the -1/-1 pick) ·
#//           reqboundary=OnAttackBuffDebuff_SurvivesTheRequestBoundary ·
#//           cross-arena=OnAttackBuffSelfDebuffAnEnemySpaceUnit_BaseTakesTheBuffedPower (a GROUND
#//           attacker debuffs a SPACE unit) · combat-damage=same section (the base takes the BUFFED
#//           power, so the +1/+1 lands before damage) · duration=OnAttackBuffAndDebuffLastOnlyForThisPhase ·
#//           decline: only the -1/-1 half is legitimately declinable; the +1/+1 half is worded as
#//           mandatory and is deliberately NOT covered here (see the open note in the report).
#//
#// LAW_031 Bossk — "On Attack: Give A UNIT +1/+1 for this phase." The noun is unqualified: it names no
#// controller and no arena, so per CR the pool is EVERY unit in play — both arenas, both sides, and
#// deployed leaders count as units. Bossk attacks on the ground, yet friendly and enemy SPACE units are
#// still legal picks. Board: P1 ground = Bossk(0), SOR_095 Battlefield Marine(1), deployed SOR_006
#// Emperor Palpatine(2); P1 space = SOR_237 Alliance X-Wing(0); P2 ground = SOR_046 Consular Security
#// Force(0), deployed SOR_010 Darth Vader(1); P2 space = SOR_178 Cartel Spacer(0). The pick is left
#// pending so the offer itself is what gets asserted.

## GIVEN
CommonSetup: brk/bgw/{myLeader:SOR_006:1:1:1; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1GroundArena: [LAW_031:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECISIONTOOLTIP:Choose_a_unit_to_give_+1/+1
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&myGroundArena-2&mySpaceArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# OnAttackDebuffOfferIsAlsoEveryUnitInPlay_IncludingTheJustBuffedOne
#// LAW_031 Bossk — the second half ("You may give a unit -1/-1 for this phase") is worded with the same
#// unqualified noun, so its pool must be the SAME seven units, not a narrowed or complementary set. In
#// particular the unit that just took the +1/+1 (Bossk itself here) is still a legal pick: nothing in the
#// text removes it, and the two halves are independent effects rather than one "another unit" clause.
#// Same board as the buff-offer section; the second pick is left pending to assert the offer.

## GIVEN
CommonSetup: brk/bgw/{myLeader:SOR_006:1:1:1; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1GroundArena: [LAW_031:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_unit_to_give_-1/-1
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&myGroundArena-2&mySpaceArena-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# OnAttackBuffSelfDebuffAnEnemySpaceUnit_BaseTakesTheBuffedPower
#// LAW_031 Bossk — the two halves aimed across the arena line: Bossk buffs ITSELF (3/5 -> 4/6) and
#// debuffs an enemy SPACE unit, SOR_178 Cartel Spacer (2/3 -> 1/2), while attacking on the ground.
#// Intended: On Attack resolves during the attack, before combat damage, so the base takes Bossk's
#// BUFFED power — 4, not its printed 3. Every unit that was NOT named keeps its printed power
#// (SOR_095 3, deployed SOR_006 4, SOR_237 2, SOR_046 3, deployed SOR_010 5): a single-target buff and a
#// single-target debuff must not splash.

## GIVEN
CommonSetup: brk/bgw/{myLeader:SOR_006:1:1:1; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1GroundArena: [LAW_031:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_178:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_031
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HP:6
P2SPACEARENAUNIT:0:CARDID:SOR_178
P2SPACEARENAUNIT:0:POWER:1
P2SPACEARENAUNIT:0:HP:2
P2BASEDMG:4
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:2:POWER:4
P1SPACEARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:1:POWER:5

---

# OnAttackBuffAndDebuffLastOnlyForThisPhase
#// LAW_031 Bossk — both halves are written "FOR THIS PHASE", so neither survives the phase change. Same
#// flow as OnAttackBuffSelfDebuffAnEnemySpaceUnit_BaseTakesTheBuffedPower, then the action phase is ended
#// and the game crosses regroup back into a fresh action phase: Bossk is back to its printed 3/5 and
#// SOR_178 Cartel Spacer back to its printed 2/3. Decks are seeded so the regroup draw decks nobody.

## GIVEN
CommonSetup: brk/bgw/{myLeader:SOR_006:1:1:1; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1GroundArena: [LAW_031:1:0 SOR_095:1:0]
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>Pass

## EXPECT
PHASE:MAIN
P1GROUNDARENAUNIT:0:CARDID:LAW_031
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:5
P2SPACEARENAUNIT:0:CARDID:SOR_178
P2SPACEARENAUNIT:0:POWER:2
P2SPACEARENAUNIT:0:HP:3

---

# MandatoryBuffAutoResolvesWhenItIsTheOnlyTarget
#// LAW_031 — "Give a unit +1/+1 for this phase. YOU MAY give a unit -1/-1 for this phase." Only the
#// SECOND sentence is optional, so the +1/+1 is MANDATORY and must resolve whenever a legal target
#// exists.
#// DISCRIMINATOR: with Bossk as the ONLY unit in play the mandatory half has exactly one legal target,
#// so it AUTO-RESOLVES with no prompt (a mandatory single-target choose is a forced resolve) and the
#// lone `-` answers the still-optional debuff. Base therefore takes the BUFFED 4 and nothing is left
#// pending.
#// Regression guard: both halves used to be declinable, so that `-` declined the BUFF instead, leaving
#// the debuff prompt pending — base 0 and a dangling decision. A "you may" on the first half cannot
#// auto-resolve, which is exactly what this section detects.

## GIVEN
CommonSetup: bgk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_031:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P1NODECISION

---

# TheTwoHalvesRaiseDISTINCTPrompts
#// LAW_031 — the +1/+1 and -1/-1 prompts offer the SAME pool (every unit in play), so with identical
#// tooltips their pending states were byte-identical and a player could not tell which one they were
#// answering. That is what a "spurious duplicate prompt" report on this card actually was.
#// This pins the first prompt's tooltip as the buff-specific one. Left UNANSWERED so the pending
#// decision can be read.

## GIVEN
CommonSetup: bgk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_031:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECISIONTOOLTIP:Choose_a_unit_to_give_+1/+1
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
