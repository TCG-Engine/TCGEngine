# WhenDefeatedHealBase
#// LAW_097 Imperial Door Technician (2/2) — When Defeated: heal 2 damage from your base. Attacks SOR_046
#// (3/7) and dies; P1's base (damaged 2) heals to 0.

## GIVEN
CommonSetup: brk/bgw/{myBaseDamage:2}
P1OnlyActions: true
WithP1GroundArena: LAW_097:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1BASEDMG:0

---

# WhenDefeatedHealsNewControllersBase
#// LAW_097 Imperial Door Technician — "When Defeated: Heal 2 damage from YOUR base." If an opponent
#// takes control of the technician (via No Glory, Only Results, JTL_043) and defeats it, the heal is
#// from the new controller's base. P2 steals & defeats P1's technician, healing P2's base 5 -> 3.

## GIVEN
CommonSetup: grk/bbk/{theirBaseDamage:5}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP2Resources: 8
WithP2Hand: JTL_043
WithP1GroundArena: LAW_097:1:0

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:0
P2BASEDMG:3

---

# HealIsCappedAtTheDamageActuallyOnTheBase
#// LAW_097 Imperial Door Technician — "Heal 2 damage from your base" cannot heal below zero. With only 1
#// damage on P1's base the heal removes that 1 and stops; the base does not go negative and no credit is
#// banked against future damage. Boundary partner of WhenDefeatedHealBase, where the base carries the full
#// 2 and ends at 0 as well — the pair separates "healed exactly what was there" from "subtracted 2".

## GIVEN
CommonSetup: brk/bgw/{myBaseDamage:1}
P1OnlyActions: true
WithP1GroundArena: LAW_097:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:0

---

# NoChoiceIsOffered_TheEnemyBaseIsNeverATarget
#// LAW_097 Imperial Door Technician — unlike the unqualified "a base" family (LAW_058, LAW_181, LAW_189),
#// this one is printed "heal 2 damage from YOUR base", so there is no target choice at all: the heal is
#// applied straight to the controller's base and the damaged ENEMY base is untouched. Both bases carry
#// damage here so a pool-based implementation would have had to prompt.

## GIVEN
CommonSetup: brk/bgw/{myBaseDamage:2; theirBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: LAW_097:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1NODECISION
P1BASEDMG:0
P2BASEDMG:5
