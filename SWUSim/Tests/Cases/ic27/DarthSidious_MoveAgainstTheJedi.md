# RestoreThreeHeals_ThenDealsThreeToAnEnemyUnit
#// IC27_026 Darth Sidious (Move Against the Jedi) — 7 cost, 5/8, Vigilance+Villainy, Ground,
#//   Force/Separatist/Sith (unique). Restore 3 is printed (auto-wired).
#// Text: "When you heal damage from your base: Deal that much damage to an enemy unit."
#// His own Restore is the natural trigger. Base at 5 damage heals the full 3 (5 -> 2), so exactly 3
#// lands on the lone enemy unit.

## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: IC27_026:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:5
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# BaseNearlyFull_HealsLess_SoDealsLess
#// THE CLAMP — the headline test. "That much" is the damage ACTUALLY healed, not the printed Restore
#// value. With only 1 damage on the base, Restore 3 heals 1 and the enemy unit takes 1, not 3.

## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:1}
P1OnlyActions: true
WithP1GroundArena: IC27_026:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# UndamagedBase_HealsNothing_DealsNothing
#// THE ZERO CASE: healing 0 is not "healing damage from your base", so nothing triggers at all.

## GIVEN
CommonSetup: bbk/bbk/{}
P1OnlyActions: true
WithP1GroundArena: IC27_026:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# NoEnemyUnit_HealStillHappens
#// NO-VALID-TARGET: the damage half fizzles cleanly with no enemy unit on the board, but the heal
#// (a separate keyword) still resolves — the two are not gated on each other.

## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: IC27_026:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P2BASEDMG:5
P1NODECISION

---

# ChoosesAmongMultipleEnemyUnits
#// With two enemy units this is a real choice, and only the chosen one takes the damage.
#// Both are chosen to SURVIVE 3 damage so the assertion reads a damage value rather than an
#// empty arena.

## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: IC27_026:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:3

---

# FriendlyUnitIsNotATarget
#// SCOPE: "an ENEMY unit" — with a friendly unit also on the board the damage must not be aimable at
#// it. Only the single enemy is eligible, so the choose auto-resolves onto it.

## GIVEN
CommonSetup: bbk/bbk/{myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: IC27_026:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:2
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:3
