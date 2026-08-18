# OnAttackDealDamagedGround
#// LAW_079 K-2SO (3/5, Ambush) — On Attack: you may deal 3 damage to a damaged ground unit. Attacks the
#// base; hit the pre-damaged enemy SOR_046 (2 -> 5).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_079:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# OnAttackDamageDeclined_TargetKeepsItsDamage
#// LAW_079 K-2SO — DECLINE branch. "You may deal 3 damage to a damaged ground unit": with a legal
#// pre-damaged target present the offer is refusable, and refusing leaves that unit on its original 2
#// damage rather than 5. The base still takes K-2SO's printed 3 from combat, which proves the attack
#// itself resolved and the decline did not abandon it.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_079:1:0
WithP2GroundArena: SOR_046:1:2

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:3
P1NODECISION

---

# OfferPool_DAMAGEDGroundUnitsOnly_EitherSide
#// LAW_079 K-2SO — "deal 3 damage to a DAMAGED GROUND unit" applies three filters at once and no
#// controller word, so the pool is every damaged ground unit on either side. Discriminating board: the
#// friendly damaged SOR_046 and the enemy damaged SEC_080 are IN; the enemy UNDAMAGED SOR_095 is OUT on
#// damage; the friendly damaged SPACE SOR_178 is OUT on arena; and K-2SO himself is undamaged and OUT.
#// Both existing sections have a single damaged enemy and auto-resolve, so neither can see a pool
#// narrowed to the opponent or widened to undamaged units.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_079:1:0
WithP1GroundArena: SOR_046:1:2
WithP1SpaceArena: SOR_178:1:1
WithP2GroundArena: SEC_080:1:1
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1SELECTABLEEXACT:myGroundArena-1&theirGroundArena-0

---

# NoDamagedGroundUnitAnywhere_NoOfferAtAll
#// LAW_079 K-2SO — the negative. With every unit on the board undamaged, the "you may deal 3 damage to a
#// damaged ground unit" has no legal target: no decision is raised and nothing takes damage from the
#// ability. The attack itself still lands its 3 on the enemy base.

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_079:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:3
