# TwoLeaders_DefeatByUID
#// Twin Suns Phase 2: two DIFFERENT leaders of the same force-side (both Villainy, per CR) BOTH deployed
#// as units — leader 0 = Moff Gideon (SHD_007) @ ground index 0, leader 1 = Darth Vader (IBH_053) @ index 1.
#// P1 attacks a P2 blocker (SOR_039, 8 power) WITH leader 1's unit; the blocker defeats leader 1's 8-HP
#// Vader unit on the counter. The defeat must reset LEADER 1 (matched by its DeployedUniqueID), NOT leader 0.
#// Leader 1 sits at index 1, so a "first-live" reset would wrongly reset leader 0 (index 0) and fail this;
#// only DeployedUniqueID/index-matching resets the correct one.

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:SHD_007;
  myLeaderDeployed:1;
  myLeader2:IBH_053:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1LEADERCOUNT:2
P1LEADER0DEPLOYED:true
P1LEADER1DEPLOYED:false

---

# TwoLeaders_IndependentDeploy
#// Twin Suns Phase 2: two DIFFERENT same-side leaders (leader 0 = Moff Gideon SHD_007, leader 1 = Darth
#// Vader IBH_053, both Villainy). Deploying LEADER 1 deploys ONLY leader 1 (index-threaded). Vader's deploy
#// threshold is 6 (WithP1Resources: 6). DeployLeader:1 must flip leader 1's Deployed flag, not leader 0's.

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:SHD_007;
  myLeader2:IBH_053
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6

## WHEN
- P1>DeployLeader:1

## EXPECT
P1LEADERCOUNT:2
P1LEADER0DEPLOYED:false
P1LEADER1DEPLOYED:true

---

# TwoLeaders_IndependentExhaust
#// Twin Suns Phase 2: two DIFFERENT same-side leaders (leader 0 = Moff Gideon SHD_007, leader 1 = Darth
#// Vader IBH_053, both Villainy). Using LEADER 1's action exhausts ONLY leader 1 (index-threaded). Leader 1
#// is Vader ("Action [1 resource, Exhaust]: deal 1 to a base"); firing index 1 must exhaust the clicked
#// INSTANCE (leader 1) and leave leader 0 (Gideon, index 0) READY — catching a "first-live" exhaust bug.

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:SHD_007;
  myLeader2:IBH_053
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 1

## WHEN
- P1>UseLeaderAbility:1
- P1>AnswerDecision:theirBase-0

## EXPECT
P1LEADERCOUNT:2
P1LEADER0:READY
P1LEADER1:EXHAUSTED
P2BASEDMG:1
