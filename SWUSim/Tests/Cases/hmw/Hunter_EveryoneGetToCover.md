# ShieldThenAttack_BothModesResolve
#// HMW_035 Hunter - Everyone Get to Cover! (Command/Vigilance/Heroism, Clone, cost 6, 4/7 Ground,
#// unique, Legendary) — "When Played: Choose two. You may choose the same option more than once:
#//   • Give a Shield token to a unit.
#//   • Attack with a unit, even if it's exhausted. It can't attack bases for this attack."
#// COVERAGE: offer=ShieldOfferSpansBothSidesAndExcludesBases (the Shield pool) +
#//           AttackTargetOfferExcludesTheBase (the attack pool — the base restriction asserted as a
#//           POOL, which is strictly stronger than asserting the base took 0) ·
#//           negative=NoEnemyUnits_AttackModeUnavailable + EnemyInOtherArenaOnly (the attack mode's
#//           availability gate, asserted on STATE: the would-be attacker is still exhausted/ready as
#//           it started, never stranded) ·
#//           boundary=ExactlyTwoPicks_NoThirdPrompt (two, not one and not three) ·
#//           control=StolenUnitIsALegalAttacker · reqboundary=RequestBoundary_SecondPickSurvives ·
#//           decline=N/A — "Choose two" is MANDATORY and neither mode prints "may". The only "may" on
#//           the card attaches to the REPEAT ("you may choose the same option more than once"), which
#//           is what ShieldTwice_/AttackTwice_ cover. Contrast SOR_240 Fleet Lieutenant, whose printed
#//           "You may attack with a unit" makes ITS attacker choice declinable.
#// ⚠ The second option is SOR_110 Frontline Shuttle's sentence word for word, so it reuses SOR_110's
#//   two pieces: SWUUnitsWithNonBaseAttackTarget (friendly units that have a NON-BASE target — which is
#//   also the LAW_065 fizzle guard) and BeginSWUAttack(noBases: true).
#// ⚠ The mode list is recomputed BEFORE EACH PICK, not built once: the first pick can remove the last
#//   enemy unit and make the attack mode illegal for the second. FirstAttackKillsLastEnemy pins that.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:Attack
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:HMW_035
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1NODECISION

---

# ShieldTwice_SameOptionMayRepeat
#// HMW_035 — "You may choose the same option more than once." The shared MODAL_CHOOSE driver used by
#// the SOR aspect events REMOVES a chosen label before the next pick, which is right for a plain
#// "Choose two" and wrong here — so this section is the whole reason HMW_035 cannot just reuse it.
#// Two Shields, on two different units.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Shield
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:HMW_035
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# ShieldTwice_SameUnitStacksTwoShields
#// HMW_035 — repeating the option AND repeating the target. Nothing says "another unit", so both
#// Shields may land on the same body; Hunter itself is a legal target for its own ability.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:Shield
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_035
P1GROUNDARENAUNIT:1:SHIELDCOUNT:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# AttackTwice_SameExhaustedUnitSwingsAgain
#// HMW_035 — the repeat on the ATTACK mode, which only works because of "even if it's exhausted":
#// attacking exhausts the attacker, so the second swing is by an exhausted unit. Consular Security
#// Force (3/7) hits a 3/1 Death Star Stormtrooper, then a 3/3 Dark Trooper; it takes 0 back from the
#// 3/1 (it dies first? no — combat damage is simultaneous, so it takes 3 from the Stormtrooper too)
#// and 3 from the Dark Trooper, ending on 6 damage and alive on 7 HP with both enemies dead.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Attack
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:Attack
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:6
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1NODECISION

---

# ShieldOfferSpansBothSidesAndExcludesBases
#// HMW_035 — the Shield pool, left PENDING and asserted directly. "Give a Shield token to a unit"
#// carries no friendly/enemy qualifier, so per CR it spans BOTH sides; and it says "a unit", so
#// neither base is in the pool. Four legal targets, so nothing auto-resolves and there is a real offer
#// to inspect. Answering a target would prove only that the BRANCH works, never the POOL.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirGroundArena-1
P1HASDECISION

---

# ShieldOnAnEnemyUnit
#// HMW_035 — and the pool is not merely offered, it RESOLVES: the Shield genuinely lands on an enemy
#// unit. Rarely what a player wants, but it is what the card says, and a friendly-only implementation
#// passes every other section in this file.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:Shield
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1NODECISION

---

# AttackTargetOfferExcludesTheBase
#// HMW_035 — "It can't attack bases for this attack", asserted as a POOL rather than as an outcome.
#// Two enemy units, so the attack-target choice really appears; the enemy base must not be in it.
#// Asserting only "the base took 0 damage" would pass against an implementation that offered the base
#// and simply had it not chosen.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Attack
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1
P1HASDECISION

---

# NoEnemyUnits_AttackModeUnavailable_NothingIsStranded
#// HMW_035 — the LAW_065 fizzle trap, and the reason the mode list is filtered rather than the attack
#// being allowed to fail. With no enemy unit the attack mode can only fizzle, and BeginSWUAttack
#// READIES the attacker for the "even if it's exhausted" clause before it looks for a target — so
#// offering it would hand the player a free ready on an aborted attack. The mode is therefore not
#// offered at all: only Shield remains, both picks resolve to it, and the assertion is on STATE — the
#// exhausted Security Force is STILL EXHAUSTED.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:HMW_035
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P2BASEDMG:0
P1NODECISION

---

# EnemyInOtherArenaOnly_AttackModeUnavailable
#// HMW_035 — the same gate, reached by arena rather than by an empty board: the only enemy unit is in
#// SPACE and every friendly unit is on the GROUND, so no friendly unit has a non-base target. A gate
#// written as "does the opponent control any unit at all" answers YES here and wrongly offers the mode.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1GROUNDARENAUNIT:1:CARDID:HMW_035
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
P1NODECISION

---

# FirstAttackKillsLastEnemy_SecondPickRecomputesToShieldOnly
#// HMW_035 — the sharpest section in the file: the pool must be built at DRAIN TIME, per pick, not
#// once up front. The first pick attacks and removes the only enemy unit, which makes the attack mode
#// illegal for the second pick — so the second pick has just one mode left and resolves straight to
#// the Shield target with no mode prompt. An implementation that snapshots the labels once offers
#// "Attack" again here and strands the chosen attacker ready.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Attack
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:CARDID:HMW_035
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P2BASEDMG:0
P1NODECISION

---

# ExactlyTwoPicks_NoThirdPrompt
#// HMW_035 — "Choose TWO": not one, not three. Two Shields are taken and the board is then quiet, with
#// exactly two Shield tokens between the two units and no decision left pending. The count assertion is
#// what catches an off-by-one in the picksLeft chain in either direction.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:Shield
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1NODECISION

---

# RequestBoundary_SecondPickSurvives
#// HMW_035 — the request-boundary cell, and it is a real one rather than a formality: the picks-left
#// counter and the block index are written when the first mode is chosen and read when the second
#// picker is built, which in production happens in a FRESH PROCESS. Held in a transient global they
#// would be gone and the second pick would silently never appear. Same flow as
#// ShieldThenAttack_BothModesResolve with a boundary between the two picks.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Shield
- P1>AnswerDecision:myGroundArena-1
- P1>SimulateRequestBoundary
- P1>AnswerDecision:Attack
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:HMW_035
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1NODECISION

---

# StolenUnitIsALegalAttacker
#// HMW_035 — the control cell. "Attack with a unit" means a unit you CONTROL, not one you own: the
#// Security Force sits in P1's arena OWNED BY P2 (a take-control effect), and it must be offered as an
#// attacker and swing normally. An owner-scoped collection would not find it.
#// ⚠ Index order: a `Controlled` unit seeds AFTER the plain seeded ones, and the PLAYED Hunter is
#// appended last — so the arena is Battlefield Marine (0), the stolen Security Force (1), Hunter (2).
#// Getting this backwards is not a harmless typo: the first draft of this section answered with
#// Hunter as the attacker, and it PASSED — every behavioural assertion held while the stolen unit was
#// never exercised at all. The DAMAGE assertion on index 1 is what pins that the stolen unit swung.
#// After it kills the only enemy the attack mode is gone, so the second pick auto-resolves to Shield.

## GIVEN
CommonSetup: gbw/rrk/{myResources:6}
P1OnlyActions: true
WithP1Hand: HMW_035
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaControlled: SOR_046:2
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Attack
- P1>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:2:CARDID:HMW_035
P1GROUNDARENAUNIT:2:SHIELDCOUNT:1
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2BASEDMG:0
P1NODECISION
