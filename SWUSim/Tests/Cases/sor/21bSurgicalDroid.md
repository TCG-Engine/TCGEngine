# OnAttack_HealsAnotherUnit
#// SOR_059 2-1B Surgical Droid (1/3) — On Attack: You may heal 2 damage from another unit.
#// The Droid attacks the enemy base; the trigger offers to heal another unit. Battlefield
#// Marine (the only other unit, pre-damaged 2) auto-resolves and is healed to 0 damage.
#// COVERAGE: offer=Offer_ExcludesSelfIncludesEnemyUnits (pending SELECTABLEEXACT: every OTHER
#//           unit, both sides, never the Droid itself) · reqboundary=OnAttack_HealsAnotherUnit
#//           (the heal answer arrives in a separate request from the attack) ·
#//           control=HealsAnEnemyUnit ("another unit" crosses the seat line) · boundary
#//           pair=HealsOneDamageToFull (1→0, heal caps at damage) + HealsExactlyTwo_NotToFull
#//           (3→1, never more than 2) · decline=DeclinesTheHeal ("you may" answered '-')

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0    # 2-1B Surgical Droid (ready) — attacker, idx 0
WithP1GroundArena: SOR_095:1:2    # Battlefield Marine with 2 damage — idx 1, the heal target

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:1

---

# Offer_ExcludesSelfIncludesEnemyUnits
#// Intended: "another unit" — the heal pool is every OTHER unit in play, friendly AND enemy,
#// and never the attacking Droid itself. All three other units carry damage; the decision is
#// left pending so the exact pool can be inspected.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0    # 2-1B Surgical Droid — attacker, must NOT be in its own pool
WithP1GroundArena: SOR_046:1:3    # Consular Security Force, 3 damage
WithP1GroundArena: SEC_080:1:1    # Imperial Dark Trooper, 1 damage
WithP2GroundArena: SOR_095:1:2    # enemy Battlefield Marine, 2 damage

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&myGroundArena-2&theirGroundArena-0

---

# HealsOneDamageToFull
#// Intended: a target with only 1 damage is healed to FULL — "heal 2" caps at the damage
#// present. The Dark Trooper (1 damage) goes to 0; the other damaged units are untouched and
#// the Droid ends the attack exhausted.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SEC_080:1:1
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-2

## EXPECT
P1GROUNDARENAUNIT:2:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:1

---

# HealsExactlyTwo_NotToFull
#// Intended: the heal is exactly 2, not "to full" — the Security Force (3 damage) drops to 1.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SEC_080:1:1
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P1GROUNDARENAUNIT:2:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:1

---

# HealsAnEnemyUnit
#// Intended: "another unit" is not restricted to friendly units — the enemy Battlefield
#// Marine (2 damage) is a legal pick and is healed to 0.

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SEC_080:1:1
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:2:DAMAGE:1
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:1

---

# DeclinesTheHeal
#// "You MAY heal" — the trigger can be declined ('-'): no unit is healed, the attack itself
#// still resolves normally (base takes 1, the Droid ends exhausted).

## GIVEN
CommonSetup: ggw/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0
WithP1GroundArena: SOR_046:1:3
WithP1GroundArena: SEC_080:1:1
WithP2GroundArena: SOR_095:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:3
P1GROUNDARENAUNIT:2:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:1
