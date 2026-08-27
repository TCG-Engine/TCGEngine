# DeployedOnAttackTwoDroidsRestore
#// TS26_01 Count Dooku (leader deployed, 6/7) — Restore 2 + On Attack: create 2 Battle Droid tokens. The
#// deployed Dooku attacks the enemy base: Restore 2 heals P1's base (3 → 1), 2 Battle Droids are created,
#// and 6 combat damage hits the enemy base.
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_01:1:1;myBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1GROUNDARENACOUNT:3
P1BASEDMG:1
P2BASEDMG:6

---

# FrontBothHealAndDroid
#// TS26_01 Count Dooku (leader front) — Action [Exhaust]: choose 2 players; they each heal 1 from their
#// base and create a Battle Droid token. In 2-player, both bases heal 1 (3 → 2) and both players get a
#// Battle Droid.
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_01;myBaseDamage:3;theirBaseDamage:3}
SkipPreGame: true
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1BASEDMG:2
P2BASEDMG:2
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P1LEADER:EXHAUSTED

---

# TwinSuns_ChooseTwoPLAYERS_NotJustBothSeats
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — "Choose 2 PLAYERS. They each heal 1 damage from their base
#// and create a Battle Droid token." Forced at two seats (both), a real pick of 2 out of N above that.
#// It always resolved to the caster + OtherPlayer($player).
#// P1 picks SEATS 3 and 4 — its own TEAMMATE and one opponent — and neither is the caster: P3 and P4 each
#// heal (4 → 3) and gain a droid, while P1's own base stays on 3 and seat 2 is untouched. The old code
#// would have healed P1 and P2 instead, so all four assertions move.
## GIVEN
CommonSetup: bbk/rrk/{myLeader:TS26_01;myBaseDamage:3}
SkipPreGame: true
WithTeams: true
P1OnlyActions: true
WithGamePhase: ActionPhase
WithP3Base: SOR_019:4
WithP4Base: SOR_019:4
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:P3
- P1>AnswerDecision:P4
## EXPECT
SEATCOUNT:4
P3BASEDMG:3
P4BASEDMG:3
P1BASEDMG:3
P3GROUNDARENACOUNT:1
P4GROUNDARENACOUNT:1
