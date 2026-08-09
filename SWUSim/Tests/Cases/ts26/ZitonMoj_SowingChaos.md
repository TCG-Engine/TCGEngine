# OnAttackDealsToEachPlayersUnit
#// TS26_29 Ziton Moj (Unit 4/4, cost 4) — Ambush. On Attack: for each player, deal 1 damage to a unit
#// that player controls. Ziton attacks LAW_124; the caster deals 1 to its own SEC_080 and 1 to the enemy
#// LAW_124 (which then takes 4 more from combat = 5). Ziton dies to LAW_124's counter.
## GIVEN
CommonSetup: ryk/rrk
WithP1GroundArena: [TS26_29:1:0 SEC_080:1:0]
WithP2GroundArena: LAW_124:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:5

---

# WithNoEnemyUnitsOnlyTheFriendlySideTakesDamage
#// TS26_29 Ziton Moj — "FOR EACH PLAYER, deal 1 damage to a unit that player controls" resolves per
#// player. P2 controls no units, so only P1's SEC_080 takes its 1; Ziton's attack on P2's base still
#// lands for 4.

## GIVEN
CommonSetup: ryk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [TS26_29:1:0 SEC_080:1:0]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P2BASEDMG:4
