# JTL_096 Blue Leader — Ambush + "When Played: pay 2 → move to the ground arena + 2 Experience" BOTH
# trigger on play. With an enemy SPACE unit present at collection, Ambush has a target so TWO entry
# triggers fire and P2 orders them. P2 resolves the When-Played FIRST (Blue Leader → ground as a 5/5),
# then Ambush must re-resolve the now-moved unit and attack the enemy GROUND unit (JTL_002, a 4/7 leader
# unit at 2 damage), defeating it.
#
# Regression for the stale-mzID fizzle (repro of game 2151): before the fix the Ambush entry-trigger held
# Blue Leader's original (space) mzID; the When-Played move-to-ground marked that slot removed, so
# GetZoneObject returned null and Ambush silently fizzled (no attack, enemy leader survived). The fix
# carries the unit's UID on the Ambush trigger and re-resolves it at dispatch.

## GIVEN
CommonSetup: ngw/ngw/{
  myLeader:JTL_002:true:true:true:2:0;
  myBase:SEC_026;
  myBaseDamage:14;
  theirLeader:ASH_005:true:false:false:0;
  theirBase:JTL_024;
  theirBaseDamage:8;
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 2
WithP1Resources: 7:SOR_095:1
WithP2Resources: 5:SOR_095:1,2:SOR_095:0
WithP1SpaceArena: [JTL_039:0:0]
WithP2GroundArena: [ASH_105:0:0]
WithP2Hand: [SEC_051 JTL_096]

## WHEN
- P2>PlayHand:1
- P2>ResolveTrigger:WhenPlayed
- P2>AnswerDecision:YES
- P2>AnswerDecision:YES
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
# When-Played moved Blue Leader to P2's ground (joining Bo-Katan at idx 0) as a 5/5 (3/3 + 2 Experience) …
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:JTL_096
P2GROUNDARENAUNIT:1:UPGRADECOUNT:2
P2GROUNDARENAUNIT:1:POWER:5
P2GROUNDARENAUNIT:1:HP:5
# … then Ambush re-resolved the moved unit and defeated the enemy ground leader (JTL_002 4/7 @ 2 dmg).
P1GROUNDARENACOUNT:0
