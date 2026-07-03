# ASH_005 Luke Skywalker (DEPLOYED unit side, 6/7) — "When a friendly unit's attack ends: Heal 2 damage
# from that unit or from your base." Repro of game 2088: deployed Luke attacks the enemy base, takes no
# counter (0 damage on him), so the only damaged heal target is P1's own base (7 → 5). Single target
# auto-resolves — no decision.
## GIVEN
CommonSetup: gbw/brk/{
  myLeader:ASH_005:1:1:1;
  myBaseDamage:7;
}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1BASEDMG:5
P2BASEDMG:6
P1GROUNDARENAUNIT:0:CARDID:ASH_005
P1GROUNDARENAUNIT:0:EXHAUSTED
