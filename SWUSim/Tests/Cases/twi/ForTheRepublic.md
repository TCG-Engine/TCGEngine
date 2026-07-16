# CostsLessWith3Republic
#// TWI_051 For The Republic (Upgrade, cost 3, Vigilance/Heroism) — "If you control 3 or more Republic
#// units, this upgrade costs 2 resources less to play." With 3 friendly Clone Trooper (Republic) tokens
#// in play, the upgrade costs 1. Playing it with only 1 ready resource succeeds (would cost 3 = fail
#// without the discount) and attaches to a clone. Host choice → attach to index 0.

## GIVEN
CommonSetup: bbw/rrk/{myResources:1;handCardIds:TWI_051}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1HANDCOUNT:0
P1RESAVAILABLE:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# GrantsRestore2WhileCoordinate
#// TWI_051 For The Republic — "Attached unit gains: 'Coordinate - Restore 2.'" With 3 friendly units
#// (Coordinate active), the host clone (with TWI_051 attached) heals 2 from its own base when it
#// attacks. P1 base pre-damaged to 5 → after the attack it's healed to 3. Host power = 2 (clone) + 2
#// (TWI_051) = 4 → P2 base takes 4.

## GIVEN
CommonSetup: bbw/rrk/{myResources:0;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArena: TWI_T02:1:0
WithP1GroundArenaUpgrade: 0:TWI_051

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:4
P1BASEDMG:3
