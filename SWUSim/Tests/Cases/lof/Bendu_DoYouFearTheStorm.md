# Deal3ToEachOther
#// LOF_170 Bendu — On Attack: deal 3 damage to each other unit. Bendu attacks the base; the friendly and
#// enemy 3/7 units each take 3, and Bendu itself is unaffected.

## GIVEN
CommonSetup: rrw/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_170:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# Deal3_BothArenas
#// LOF_170 Bendu — "each other unit" is NOT arena-restricted: units in BOTH the ground and space arenas
#// (friendly and enemy) each take 3, even though Bendu is a ground unit. Bendu attacks the base; the four
#// other units (friendly/enemy × ground/space) each take 3; Bendu is untouched.

## GIVEN
CommonSetup: rrw/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_170:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_193:1:0
WithP2SpaceArena: SOR_193:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:1:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:3
P1SPACEARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:0:DAMAGE:3

---

# Defeats3OrLessHp_BothArenas
#// LOF_170 Bendu — the 3 damage defeats any other unit with 3 or less remaining HP, in either arena. Enemy
#// SOR_059 (1/3 ground) and SOR_237 (2/3 space) are both defeated when Bendu attacks the base.

## GIVEN
CommonSetup: rrw/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_170:1:0
WithP2GroundArena: SOR_059:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# ShieldAbsorbsThe3
#// LOF_170 Bendu — the 3 damage is normal (preventable) damage: a Shield token on the other unit prevents it,
#// removing the shield and leaving the unit undamaged.

## GIVEN
CommonSetup: rrw/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_170:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArenaUpgrade: 0:SOR_T02

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:SHIELDCOUNT:0

---

# AttacksUnit_DefenderTakesAbilityThenCombat
#// LOF_170 Bendu — when Bendu attacks a UNIT, the On Attack fires first (3 to every other unit, incl. the
#// defender AND a bystander), THEN combat damage lands. Bendu attacks the defender (SOR_046 3/7): it takes 3
#// then Bendu's 10 and is defeated; a second enemy 3/7 (only hit by the ability) is left at DAMAGE:3; the
#// defender's counter (power 3) deals 3 to Bendu.

## GIVEN
CommonSetup: rrw/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_170:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# NoOtherUnits_NoOp
#// LOF_170 Bendu — with no other units in play, the On Attack does nothing (no error) and the attack proceeds
#// normally: Bendu deals 10 to the base and is untouched.

## GIVEN
CommonSetup: rrw/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_170:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:10
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# DefenderDefeatedByOnAttack_CombatFizzles
#// LOF_170 Bendu — the On Attack resolves BEFORE combat damage. If Bendu attacks a small unit (SOR_059, 3 HP)
#// that the 3 damage defeats, the defender is gone by the combat-damage step: no combat damage is dealt, no
#// counter hits Bendu (it stays undamaged), and — with no Overwhelm — nothing carries to the base.

## GIVEN
CommonSetup: rrw/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_170:1:0
WithP2GroundArena: SOR_059:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
