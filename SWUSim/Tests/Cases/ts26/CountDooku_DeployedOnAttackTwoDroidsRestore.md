# TS26_001 Count Dooku (leader deployed, 6/7) — Restore 2 + On Attack: create 2 Battle Droid tokens. The
# deployed Dooku attacks the enemy base: Restore 2 heals P1's base (3 → 1), 2 Battle Droids are created,
# and 6 combat damage hits the enemy base.
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_001:1:1;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENACOUNT:3
P1BASEDMG:1
P2BASEDMG:6
