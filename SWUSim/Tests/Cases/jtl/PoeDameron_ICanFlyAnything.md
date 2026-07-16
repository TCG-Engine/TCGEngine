# Hop_SecondBlocked
#// JTL_013 Poe Dameron (LEADER) — once-per-round hop guard: second hop is blocked.
#// Setup: two SOR_225 (TIE/ln Fighter) in Space arena. Poe pre-attached to index-0 via WithP1SpaceArenaUpgrade.
#// With 2 ready resources: first hop (UseUnitAbility on index-0 host) → Poe moves to index-1, costs 1 resource.
#// Second hop attempt (UseUnitAbility on index-1 host) → guard fires (SWU_POE_013_HOP_USED is set),
#// SWUUnitActionAffordable returns false, action is a no-op; no resource spent.
#// Assert: Poe still on index-1, P1RESAVAILABLE:1, no pending decision.

## GIVEN
CommonSetup: grw/grw/{
  myLeader:JTL_013;
  myBase:SOR_022;
  theirLeader:JTL_013;
  theirBase:SOR_022
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaUpgrade: 0:JTL_013

## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>UseUnitAbility:mySpaceArena-1

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_225
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_013
P1RESAVAILABLE:1
P1NODECISION

---

# Hop_ToEmptyVehicle
#// JTL_013 Poe Dameron (LEADER) — deployBox hop: move Poe from vehicle A to empty vehicle B.
#// Setup: two SOR_225 (TIE/ln Fighter) in Space arena. Poe attaches to index-0 via leader action.
#// Then: UseUnitAbility on index-0 host → hop → player picks index-1.
#// After: index-0 has 0 upgrades, index-1 has JTL_013 (upgradePower=2, upgradeHp=1 → host 4/2).
#// Once-per-round flag blocks a second hop the same round.
#// Resources: 1 for leader action + 1 for hop = 2 total.

## GIVEN
CommonSetup: grw/grw/{
  myLeader:JTL_013;
  myBase:SOR_022;
  theirLeader:JTL_013;
  theirBase:SOR_022
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 2
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:mySpaceArena-1

## EXPECT
P1SPACEARENACOUNT:2
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:1:CARDID:SOR_225
P1SPACEARENAUNIT:1:UPGRADECOUNT:1
P1SPACEARENAUNIT:1:UPGRADE:0:CARDID:JTL_013
P1SPACEARENAUNIT:1:POWER:4
P1SPACEARENAUNIT:1:HP:2
P1RESAVAILABLE:0

---

# LeaderAction_AttachNotLeaderUnit
#// JTL_013 Poe Dameron (LEADER) — Leader Action [1 resource, Exhaust]: flip + attach as Pilot.
#// SOR_225 (TIE/ln Fighter, power=2, hp=1) is the host Vehicle (0 pilots). JTL_013 upgradePower=2, upgradeHp=1.
#// After: host power=4, hp=2. Leader: Deployed, Exhausted, EpicAction NOT used.
#// Host is NOT a Leader Unit (JTL_013 not in leaderCanDeployAsUpgrade — explicit NOTLEADERUNIT check).
#// Resources: 1 spent on action cost. Base SOR_022 (Vigilance) + Leader JTL_013 (Aggression+Heroism).

## GIVEN
CommonSetup: grw/grw/{
  myLeader:JTL_013;
  myBase:SOR_022;
  theirLeader:JTL_013;
  theirBase:SOR_022
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 1
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_013
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:2
P1SPACEARENAUNIT:0:NOTLEADERUNIT
P1LEADER:DEPLOYED
P1LEADER:EXHAUSTED
P1LEADER:EPICAVAILABLE
P1RESAVAILABLE:0

---

# LeaderAction_NoVehicle_NoOp
#// JTL_013 Poe Dameron (LEADER) — Leader Action guard: no eligible Vehicle → no-op.
#// No friendly Vehicles present. Leader stays ready. No decision queued.
#// Also covers the 0-ready-resource guard: if resources < 1, SWULeaderActionAffordable returns false.

## GIVEN
CommonSetup: grw/grw/{
  myLeader:JTL_013;
  myBase:SOR_022;
  theirLeader:JTL_013;
  theirBase:SOR_022
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:0
P1LEADER:READY
P1LEADER:EPICAVAILABLE
P1NODECISION
