# CantAttack
#// LOF_044 Loth-Wolf — Sentinel + "This unit can't attack." Attacking the base is a no-op (no damage).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_044:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0

---

# CantAttack_EvenViaOutflank
#// LOF_044 Loth-Wolf — "This unit can't attack" applies even to an event-granted attack. P1 plays Outflank
#// (TWI_123, "Attack with 2 units one at a time") with a Battlefield Marine (SOR_095, 3 power) and Loth-Wolf
#// in play. Loth-Wolf is not a legal attacker, so only the Marine attacks the base (3 damage, not 6) and
#// Loth-Wolf stays ready. Intended: "should not be able to declare an attack with an event."

## GIVEN
CommonSetup: ggw/rrk/{myResources:1;handCardIds:TWI_123}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: LOF_044:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:1:CARDID:LOF_044
P1GROUNDARENAUNIT:1:READY
