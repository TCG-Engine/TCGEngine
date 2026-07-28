# ShootFirstAsFirstAttacker
#// LAW_219 Anakin's Podracer (3/2 ground, Ambush) — "While attacking, if no other units have attacked
#// this phase, this unit deals combat damage before the defending unit." As the first/only attacker it
#// gets SHOOT_FIRST: it attacks SOR_095 (3/3) and kills it BEFORE taking the 3 counter-damage, so the
#// 3/2 Podracer survives (without shoot-first it would trade and die).

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
