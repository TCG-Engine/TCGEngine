# CantBeAttacked
#// LOF_262 Go Into Hiding — Choose a unit; it can't be attacked this phase. P1 protects Plo Koon, so P2's
#// attempt to attack him deals no damage.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_262}
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# CanProtectEnemyUnit
#// LOF_262 Go Into Hiding — the chosen unit may be an ENEMY unit; that unit then can't be attacked this
#// phase. P1 protects the enemy 3/7 (SOR_046): P1's Plo Koon can't attack it (the attack is rejected, enemy
#// stays undamaged) but can still attack the base — proving Plo was never exhausted by the rejected attack.
#// Intended: "should prevent enemy unit from being attacked".

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_262}
WithP1GroundArena: LOF_050:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>Pass
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:6

---

# SentinelOverridesProtection
#// LOF_262 Go Into Hiding — "(unless it has Sentinel)". A protected unit that HAS Sentinel can still be
#// attacked: CR 11.a "Abilities this unit has or gains can't prevent this unit from being attacked" /
#// 705d / 867a "Units with Sentinel can always be attacked, even if they also have a 'can't be attacked'
#// ability or effect." P1 protects its own Sentinel unit (SOR_063, 2/4 Sentinel); P2's 3-power attacker
#// can (and must) still attack it, dealing 3 damage.

## GIVEN
CommonSetup: ggk/rrw/{myResources:2;handCardIds:LOF_262}
WithP1GroundArena: SOR_063:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
