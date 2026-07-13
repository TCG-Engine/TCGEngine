# Twin Suns Phase 2: two leaders BOTH deployed as units (leader 0 @ ground index 0, leader 1 @ index 1).
# P1 attacks a P2 blocker (SOR_039, 8 power) WITH leader 1's unit; the blocker defeats leader 1's 8-HP
# unit on the counter. The defeat must reset LEADER 1 (matched by its DeployedUniqueID), NOT leader 0 —
# proving the Task 4 fix. Both leaders share a CardID (IBH_053), so a "first-live" or CardID-only reset
# would fail this; only DeployedUniqueID-matching resets the correct one.

## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:IBH_053;
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
