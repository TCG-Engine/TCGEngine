# CombatBaseDamage_SacFor3
#// SEC_150 Valiant Commando (Ground, 3/3) — When this unit deals combat damage to a base: you may defeat
#//   this unit; if you do, deal 3 to that base. Attacks P2 base (3), then sacrifices for 3 more (total 6).

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_150:1:0
WithP1Hand: SEC_150

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:6
P1GROUNDARENACOUNT:0
P1NODECISION

---

# CombatBaseDamage_FriendlyUnitNoTrigger
#// SEC_150 Valiant Commando — the sac ability triggers only when HE deals combat damage to a base.
#//   A friendly Yoda (SOR_045) attacks the base for 2; Valiant's ability does not fire and he stays in play.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_150:1:0
WithP1GroundArena: SOR_045:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:2
P1NODECISION

---

# CombatBaseDamage_OverwhelmTriggersSac
#// SEC_150 Valiant Commando — overwhelm spill onto the base still counts as dealing combat damage to a
#//   base. Cody (TWI_114) Coordinate grants each other friendly unit +1/+1 and Overwhelm (3 friendly units
#//   in play), so a 4/4 Valiant defeats the 2/1 Jedha Agitator (SOR_158) and spills 3 to the base; he then
#//   sacrifices himself for 3 more = 6.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1GroundArena: SEC_150:1:0
WithP1GroundArena: TWI_114:1:0
WithP1SpaceArena: SEC_213:1:0
WithP2GroundArena: SOR_158:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:6
P1GROUNDARENACOUNT:1
P1NODECISION
