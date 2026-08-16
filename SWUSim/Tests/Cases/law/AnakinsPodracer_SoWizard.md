# ShootFirstAsFirstAttacker
#// LAW_219 Anakin's Podracer (3/2 ground, Ambush) — "While attacking, if no other units have attacked
#// this phase, this unit deals combat damage before the defending unit." As the first/only attacker it
#// gets SHOOT_FIRST: it attacks SOR_095 (3/3) and kills it BEFORE taking the 3 counter-damage, so the
#// 3/2 Podracer survives (without shoot-first it would trade and die).
#// COVERAGE: offer=N/A (no targeted ability — the only picks are standard attack-target choices, always
#//           seeded with 2+ candidates) · reqboundary=NotFirstIfAnEnemyAttacked (the attacked-this-phase
#//           flag is written on one request and read across P1>Pass + a P2 action) · control=N/A (the
#//           clause reads only which units have attacked, never who controls them) · boundary
#//           pair=ShootFirstAsFirstAttacker vs NotFirstIfAnotherFriendlyAttacked (same attack, flag
#//           present/absent) · decline=N/A (strike-first is a static combat replacement with no "you may";
#//           the Ambush decline branch is generic-keyword behavior, not this card's)

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_219:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_219
P2GROUNDARENACOUNT:0

---

# NotFirstIfAnotherFriendlyAttacked
#// LAW_219 — the "deals damage first" clause only applies if NO other unit has attacked this phase. Here a
#// friendly space unit (SOR_237) attacks the enemy base first, so when the 3/2 Podracer then attacks the
#// 3/3 Battlefield Marine it does NOT strike first: damage is simultaneous, both take 3, and both are defeated.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: LAW_219:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# NotFirstIfAnEnemyAttacked
#// LAW_219 — an ENEMY attack this phase also disqualifies the "deals damage first" clause (the card counts
#// any unit's attack). P2's SOR_046 attacks P1's base first; the Podracer then attacks the 3/3 marine and,
#// with no strike-first, trades — both units are defeated.

## GIVEN
CommonSetup: yyk/rrk/{}
WithP1GroundArena: LAW_219:1:0
WithP2GroundArena: [SOR_046:1:0 SOR_095:1:0]

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:1

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046

---

# NotFirstIfFirstAttackerDefeatedDuringAttack
#// LAW_219 — a unit that attacked and was DEFEATED during its own attack still counts as having attacked
#// this phase. P1's SEC_080 trades with an enemy SEC_080 (both defeated); the Podracer then attacks the 3/3
#// marine and, no longer the first attacker, trades and is defeated too.

## GIVEN
CommonSetup: yyk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: [LAW_219:1:0 SEC_080:1:0]
WithP2GroundArena: [SEC_080:1:0 SOR_095:1:0]

## WHEN
- P1>AttackGroundArena:1:0
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# NotFirstIfItIsTheDefender
#// LAW_219 — the clause is "while attacking", so it does nothing when the Podracer is the DEFENDER. P2's
#// 3/3 marine attacks the 3/2 Podracer: damage is simultaneous, both take 3, and both are defeated.

## GIVEN
CommonSetup: yyk/rrk/{}
WithP1GroundArena: LAW_219:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>Pass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0

---

# NoStrikeFirstAfterBounceAndReplay
#// LAW_219 — leaving play does not reset the phase's attack history. The seeded Podracer attacks the 3/3
#// marine first (striking first: the marine dies before its counter, so the Podracer survives on 0 — the
#// later replay from hand is only possible because of this). P2 then Waylays it back to P1's hand; P1
#// replays it the SAME phase and takes its Ambush attack into a 3/1 Death Star Stormtrooper. A unit HAS
#// already attacked this phase (its own earlier incarnation), so the replayed Podracer must NOT strike
#// first: damage is simultaneous and both units are defeated (with strike-first the 1-HP defender would
#// die before dealing its 3).

## GIVEN
CommonSetup: yyw/yyk/{
  myResources:3;
  theirResources:3
}
WithP1GroundArena: LAW_219:1:0
WithP2Hand: SOR_222
WithP2GroundArena: [SOR_095:1:0 SOR_128:1:0 SOR_128:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:LAW_219

---

# AmbushAttackItselfStrikesFirst
#// LAW_219's OWN Ambush attack as the phase's first attack gets the strike-first: no other unit has
#// attacked this phase, so the condition holds regardless of how the attack was initiated. Intended:
#// the 3/2 Podracer Ambush-attacks the 3/3 Battlefield Marine, kills it BEFORE the counter, and
#// survives on 0 damage (without strike-first this is a trade and the Podracer dies).

## GIVEN
CommonSetup: yyw/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_219
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_219
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENACOUNT:0

---

# ShootFirstFlagSurvivesTheRequestBoundary
#// LAW_219 — request-boundary guard for NoStrikeFirstAfterBounceAndReplay: same fixture, same flow, one
#// extra SimulateRequestBoundary inserted before the Ambush TARGET answer. Production starts a FRESH
#// process on every answered decision, so the "which units have attacked this phase" history must come
#// back out of the serialized gamestate rather than an in-memory global. The replayed Podracer's whole
#// Ambush attack — and therefore the strike-first check — resolves AFTER the boundary, and it must still
#// see that a unit already attacked this phase: damage stays simultaneous and both units die.
#// The insertion point is a genuine 2-option choose (theirGroundArena-0 / theirGroundArena-1), so the
#// boundary is not vacuous.

## GIVEN
CommonSetup: yyw/yyk/{
  myResources:3;
  theirResources:3
}
WithP1GroundArena: LAW_219:1:0
WithP2Hand: SOR_222
WithP2GroundArena: [SOR_095:1:0 SOR_128:1:0 SOR_128:1:0]

## WHEN
- P1>AttackGroundArena:0:0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_128
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:LAW_219
