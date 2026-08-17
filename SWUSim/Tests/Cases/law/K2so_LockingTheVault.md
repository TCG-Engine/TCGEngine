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
