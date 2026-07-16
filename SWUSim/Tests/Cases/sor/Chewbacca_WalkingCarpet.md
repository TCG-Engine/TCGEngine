# GrantedSentinel_ExpiresNextPhase
#// SOR_003 Chewbacca — the granted Sentinel is "for this phase" only. P1 plays SOR_237 via the leader
#// action (it gains Sentinel), then passes to end the action phase; RegroupPhaseStart expires the
#// SOR_003 phase-duration token, so the X-Wing no longer has Sentinel. It survives (undamaged), so the
#// unit is still in play — only the keyword is gone.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SOR_003;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_237

## WHEN
- P1>UseLeaderAbility
- P1>Pass

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel

---

# LeaderAction_PlaysUnit_GainsSentinel
#// SOR_003 Chewbacca (leader) — Action [exhaust]: Play a unit that costs 3 or less from your hand
#// (paying its cost). It gains Sentinel for this phase. P1 uses the leader action: Chewbacca exhausts,
#// the only ≤3 hand unit SOR_237 Alliance X-Wing (Heroism, cost 2 — Chewbacca covers Heroism) is
#// played for its full 2 (2 ready → 0), enters the space arena, and gains Sentinel via the SOR_003
#// turn-effect token.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SOR_003;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SOR_237

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1LEADER:EXHAUSTED
P1RESAVAILABLE:0
P1HANDCOUNT:0

---

# NoValidTarget_Over3Cost_Fizzle
#// SOR_003 Chewbacca — the action only plays a unit costing 3 or LESS. The hand holds SOR_046
#// Consular Security Force (Vigilance,Heroism, cost 4 — both aspects covered by Chewbacca, so it stays
#// 4), which is over the limit. P1 has 4 ready resources (enough to PAY for it), proving the gate is
#// the ≤3 cost ceiling, not affordability: no valid target → the action fizzles. Chewbacca still
#// exhausts (the action was used), the Security Force stays in hand, and no decision is pending.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:SOR_003;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: SOR_046

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:0
P1HANDCOUNT:1
P1LEADER:EXHAUSTED
P1NODECISION
