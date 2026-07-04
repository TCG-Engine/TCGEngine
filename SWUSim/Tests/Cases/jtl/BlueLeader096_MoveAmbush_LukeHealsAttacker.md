# JTL_096 Blue Leader + ASH_005 Luke Skywalker (undeployed leader) — full game-2151 chain.
# P2 plays Blue Leader; Ambush (target = P1 space Chimaera at collection) + When Played (pay 2 → move to
# ground + 2 XP) both fire. P2 resolves When-Played FIRST → Blue Leader becomes a 5/5 in the ground arena.
# Ambush re-resolves the moved unit; its only ground target is P1's Thrawn leader-unit (JTL_002 4/7 @ 2
# dmg), so it auto-fires (single target). Combat: Blue Leader deals 5 → Thrawn (2+5 ≥ 7) defeated; Thrawn
# deals 4 back → Blue Leader at 4 damage. Then Luke's "when a friendly unit's attack ends" fires: P2
# exhausts Luke to heal 1 from Blue Leader, leaving it at 3 damage (2 HP remaining).

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
- P2>AnswerDecision:YES

## EXPECT
# Blue Leader moved to P2 ground (idx 1, after Bo-Katan) as a 5/5 and survived the attack …
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:JTL_096
P2GROUNDARENAUNIT:1:POWER:5
P2GROUNDARENAUNIT:1:HP:5
# … Thrawn defeated by the Ambush attack …
P1GROUNDARENACOUNT:0
# … and Luke exhausted to heal 1 (4 counter damage − 1 heal = 3).
P2GROUNDARENAUNIT:1:DAMAGE:3
P2LEADER:EXHAUSTED
