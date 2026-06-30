# SHD_006 Jabba the Hutt (leader front) — the granted Bounty is "for this phase". After Jabba bounties
# the enemy Battlefield Marine, the action phase ends (P1 passes; P2 auto-passes under P1OnlyActions),
# RegroupPhaseStart runs SWUExpireTurnEffects('phase'), and the marine no longer has the Bounty keyword.

## GIVEN
CommonSetup: ygk/yrk/{
  myLeader:SHD_006;
  myBase:SOR_028
}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseLeaderAbility
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Bounty
