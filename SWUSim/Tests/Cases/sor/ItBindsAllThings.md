# ChooseHealLessThan3
#// COVERAGE: offer=DealOffer_ANOTHERUnit_TheHealedOneIsExcluded (the deal prompt left PENDING and its
#//           exact legal set read — three eligible bodies across both sides and both arenas so nothing
#//           auto-resolves, with the just-healed unit as the one body excluded by "another") ·
#//           decline=DeclineDeal (the "you may deal" answered with '-'; the heal still happened) ·
#//           boundary=ChooseHealLessThan3 + HealLessThan3_DealsThatMuch + ForceUnit_HealAndDeal (the
#//           "up to 3" amount at 1 / 2 / 3, with the 2 case pinned by the unit's own damage capping the
#//           choice, and "that much" tracking the amount ACTUALLY healed each time) plus
#//           NoForceUnit_HealOnly as the negative that makes the FORCE gate load-bearing ·
#//           reqboundary=SimulateRequestBoundary_HealedAmountAndUIDSurviveEveryStep (all three prompts
#//           crossed; the healed mzID and the healed AMOUNT both rebuilt from serialized state) ·
#//           control=N/A: every pool here is unqualified ("a unit" / "another unit" — SWUAllUnits(),
#//           both sides, both arenas, asserted as such in the offer section), so there is no
#//           controller-relative narrowing for an owner≠controller split to get wrong, and the effect
#//           touches no owner-relative zone — it only moves damage counters on bodies already in play.
#//           The one seat-relative clause, "if YOU control a FORCE unit", is a controller test by
#//           construction and is exercised in both directions by ForceUnit_HealAndDeal /
#//           NoForceUnit_HealOnly.
#// SOR_075 It Binds All Things — "Heal UP TO 3" — the player may choose to heal LESS than 3 even when
#// more damage is available. SOR_046 has 3 damage, but P1 chooses to heal only 1 (NUMBERCHOOSE → 1):
#// SOR_046 is left at DAMAGE:2, and "deal that much" deals only 1 to the enemy (LAW_124 → DAMAGE:1).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:2
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# DeclineDeal
#// SOR_075 It Binds All Things — the conditional damage is optional ("you may deal"). With a Force unit
#// present, P1 heals 3 from SOR_046 but DECLINES the deal (AnswerDecision:-); the enemy is untouched.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:3
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ForceUnit_HealAndDeal
#// SOR_075 It Binds All Things (Vigilance event, cost 2, Force) — "Heal up to 3 damage from a unit. If
#// you control a FORCE unit, you may deal that much damage to another unit." P1 controls a Force unit
#// (SOR_049 Obi-Wan). Healing 3 from the damaged SOR_046 (damage 3 → 0) then deals that 3 to the enemy
#// LAW_124 (4/7 → DAMAGE:3).

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:3
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# HealLessThan3_DealsThatMuch
#// SOR_075 It Binds All Things — "deal that much" equals the amount ACTUALLY healed. The heal amount is
#// capped at the unit's damage: the chosen unit only has 2 damage, so the NUMBERCHOOSE max is 2; healing
#// 2 (→ 0) makes the conditional deal 2, not 3. LAW_124 (4/7) takes DAMAGE:2.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:2
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# NoForceUnit_HealOnly
#// SOR_075 It Binds All Things — without a friendly FORCE unit, only the heal happens; no damage may be
#// dealt. P1 heals 3 from SOR_046 (damage 3 → 0); no deal decision is offered and the enemy is untouched.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:3

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# DealOffer_ANOTHERUnit_TheHealedOneIsExcluded
#// SOR_075 It Binds All Things — OFFER axis. "You may deal that much damage to ANOTHER unit": the word
#// "another" is the whole restriction and answering a target can never prove it, because a legal answer
#// looks identical whether or not the healed unit was also on the menu. So the deal prompt is left
#// PENDING and its exact legal set is read. N+1 fixture: THREE bodies remain eligible after the heal
#// (the friendly Force unit, an enemy ground unit and an enemy space unit — so nothing auto-resolves),
#// while the healed SOR_046 at myGroundArena-0 must be the one body missing from the pool. The pool is
#// otherwise unqualified — friendly and enemy, ground and space alike — which the same assertion pins.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:3

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0&theirSpaceArena-0
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0

---

# SimulateRequestBoundary_HealedAmountAndUIDSurviveEveryStep
#// SOR_075 It Binds All Things — REQUEST-BOUNDARY axis. This card is a three-answer chain (which unit to
#// heal → how much → which OTHER unit takes that much), and in production EVERY one of those prompts
#// ends the request: each answer arrives in a fresh process that has to rebuild the earlier ones from
#// serialized state. Two things must survive: the healed unit's mzID (carried into the follow-up) and
#// the amount ACTUALLY healed, which is what "that much" means. Mirrors HealLessThan3_DealsThatMuch —
#// SOR_046 holds only 2 damage, so the cap is 2 and the deal is 2, not 3 — with a boundary inserted
#// before every answer. A per-process amount would land 0 or the printed 3 instead of 2, and a lost
#// mzID would heal or exclude the wrong body.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:2
WithP1GroundArena: SOR_049:1:0
WithP2GroundArena: LAW_124:1:0
WithP1Hand: SOR_075

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:2
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:2
