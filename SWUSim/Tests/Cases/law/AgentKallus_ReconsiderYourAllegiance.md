# FrontPlayIgnoreAspect
#// LAW_003 Agent Kallus (leader front) — "Action [1 resource, Exhaust]: Play a card from your hand,
#// ignoring its aspect penalties." Kallus is Vigilance/Villainy (base Cunning), so SOR_095 (Command/
#// Heroism, cost 2) is normally 2+4=6. With the action: pay 1 resource, then play SOR_095 for just 2
#// (full penalty waived). 3 resources → 1 (action) + 2 (card) = 0 left.

## GIVEN
CommonSetup: ybk/grw/{
  myLeader:LAW_003;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: SOR_095

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1RESAVAILABLE:0

---

# Deployed_NoPlayableCard_UsableCostPaid
#// LAW_003 Agent Kallus (deployed) — CR 6.4.587.c: the deployed action's cost is [1 resource] (no self-
#// exhaust; the leader-unit side has no exhaustSelf), a game-state change, so the Action is usable even with
#// no affordable card to play. It spends 1 resource and plays nothing; the deployed unit stays ready.

## GIVEN
CommonSetup: bbk/bbk/{myLeader:LAW_003:1:1:1;myBase:JTL_019;theirBase:SOR_021;myResources:1}
SkipPreGame: true
P1OnlyActions: true

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:READY
P1RESAVAILABLE:0
