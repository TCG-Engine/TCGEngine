# ImmediateUniqueness_DefeatOwnCopy_BothUnitsDie
#// Uniqueness rule TIMING (CR 29.3.3) — the defeat happens IMMEDIATELY, mid-resolution:
#//
#//   "If a player ever has more than one copy of a unique card in play under their control at a given
#//    time, they must defeat one of them. Defeating one of the copies occurs immediately and is not a
#//    triggered ability. The player still must resolve any abilities that trigger upon either copy
#//    being played or defeated."
#//
#// "Immediately" is the whole point: the rule is NOT deferred to a priority window or to the action's
#// end. So a TRANSIENT duplicate — one that exists only part-way through a single ability's
#// resolution — still forces the choice, even though it is gone by the time the action closes.
#//
#// JTL_043 No Glory, Only Results — "Take control of a non-leader unit, THEN defeat it." Steal a copy
#// of a unique the caster already controls and, BETWEEN the two clauses, the caster controls two
#// copies. CR 29.3.3 fires there and the caster PICKS which copy dies; the card's own "then defeat
#// it" then resolves against the stolen copy. Picking your own original therefore kills BOTH units.
#//
#// ⚠ THE CHOICE IS THE ASSERTION, which is why these two sections are a PAIR. Enforcing only at
#// action close (what the engine did before) silently produces section B's board every time: the
#// stolen copy is already dead by then, so no violation is ever observed and the caster is never
#// asked. Section B alone would therefore PASS against the broken engine — only A discriminates, and
#// only B proves the fix honours the answer rather than always defeating the original.
#//
#// ⚠ FIXTURE: LOF_093 Gungi - Finding Himself is unique, ground, and has NO card text whatsoever, so
#// nothing but the uniqueness rule can fire. Do not swap in SOR_034 Del Meeko (the card the sibling
#// UniquenessRule_* fixtures use): he taxes "each event an opponent plays" by 1, which silently puts
#// No Glory out of reach at 5 resources and makes the play a no-op that reads as a rules failure.
#//
#// Both players controlling a copy is legal (CR 29.3.5 — the limit is per-player); it is P1 gaining
#// the second copy that breaks the rule. Defeated units go to their OWNER's discard, so the two
#// copies land in different piles, which is what proves WHICH copy died rather than just how many.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP1GroundArena: LOF_093:1:0
WithP2GroundArena: LOF_093:1:0

## WHEN
# Take control of P2's copy. P1 now controls two Gungis — the transient duplicate.
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
# CR 29.3.3 fires HERE, before "then defeat it". P1 defeats their OWN original (index 0).
- P1>ChooseMyGroundUnit:0

## EXPECT
# Both copies are gone: the original to the uniqueness rule, the stolen one to "then defeat it".
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
# P1's own copy + the spent event.
P1DISCARDCOUNT:2
# The stolen copy returns to ITS OWNER's discard, not the thief's.
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:LOF_093
P2DISCARDUNIT:0:FROM:PLAY
P1NODECISION

---

# ImmediateUniqueness_BothCopiesAliveWhenTheChoiceIsOffered
#// ⚠ THE ORDERING ASSERTION — the only section that can see it. Sections A and B pin the OUTCOME of
#// each answer, but both of their boards are reachable with the clauses in the WRONG order too: if
#// "then defeat it" runs inline (before the queued uniqueness choice), the stolen copy is already
#// dead when the player is asked, and answering still lands on the same final board by coincidence.
#// A and B therefore both pass against that ordering, which is exactly what a mutation run showed.
#//
#// CR 29.3.3's "immediately" is a statement about WHEN, so assert WHEN: stop the moment the control
#// change completes and require that BOTH copies are still in play with the uniqueness prompt
#// pending. That is only true if the rule interrupted the ability between its two clauses.
#//
#// The decision is deliberately left UNANSWERED so the prompt itself is observable.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP1GroundArena: LOF_093:1:0
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0

## EXPECT
# Both copies are in P1's arena at once — the transient duplicate CR 29.3.3 reacts to.
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:LOF_093
P1GROUNDARENAUNIT:1:CARDID:LOF_093
# The steal has happened, so P2 has lost it...
P2GROUNDARENACOUNT:0
# ...and the stolen copy is NOT in its owner's discard, so "then defeat it" has not run yet. This is
# the count that discriminates the ordering: resolve that clause inline and it would already be 1.
P2DISCARDCOUNT:0
# P1's discard holds the spent event and nothing else — playing JTL_043 discards it up front, which
# is why this is 1 rather than 0 and is not evidence about the defeat clause either way.
P1DISCARDCOUNT:1
# And the rule is asking, right here, mid-resolution.
P1DECISIONTOOLTIP:Uniqueness_rule_choose_a_copy_to_defeat

---

# ImmediateUniqueness_DefeatStolenCopy_OriginalSurvives
#// The other branch of the same choice. Defeating the STOLEN copy satisfies the uniqueness rule, so
#// "then defeat it" finds its target already gone and does nothing — P1 keeps their original. Same
#// board as section A, same prompt, opposite answer, opposite outcome.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5}
P1OnlyActions: true
WithP1Hand: JTL_043
WithP1GroundArena: LOF_093:1:0
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
# The stolen copy was appended to P1's ground arena, so it is index 1.
- P1>ChooseMyGroundUnit:1

## EXPECT
# P1's original survives, ready and undamaged.
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_093
P1GROUNDARENAUNIT:0:READY
P2GROUNDARENACOUNT:0
# Only the spent event — P1 lost no unit of their own.
P1DISCARDCOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:LOF_093
P1NODECISION

---

# ImmediateUniqueness_ClosesTheActionExactlyOnce
#// ⚠ THE CLOSE assertion, and the ONLY section without P1OnlyActions. The mid-resolution uniqueness
#// enforcement must NOT attempt the action close: the action is still in flight and owns it. The
#// close gate grants exactly one close, so an early attempt ends the action while "then defeat it"
#// is still pending — the ability finishes after the turn has already passed.
#//
#// ⚠ P1OnlyActions makes TURNPLAYER unobservable (initiative is claimed, so the opponent auto-passes
#// and a double turn-swap looks exactly like a single one). Every other section in this file sets it,
#// which is why none of them can see this and a mutation of the guard left them all green — it showed
#// up only as [ACTION-LEDGER] BLOCKED-DOUBLE-CLOSE on stderr, which the pass/fail counts never report.
#//
#// The full board is re-asserted so that "closed once" cannot be satisfied by the ability having
#// silently dropped its remaining clause.

## GIVEN
CommonSetup: bbk/bbk/{myResources:5}
WithP1Hand: JTL_043
WithP1GroundArena: LOF_093:1:0
WithP2GroundArena: LOF_093:1:0

## WHEN
- P1>PlayHand:0
- P1>ChooseTheirGroundUnit:0
- P1>ChooseMyGroundUnit:0

## EXPECT
# Exactly one action was spent, and the turn passed exactly once.
NOEXTRAACTION
TURNPLAYER:2
# The ability still resolved in full: both copies dead, each in its owner's discard.
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:2
P2DISCARDCOUNT:1
P1NODECISION
