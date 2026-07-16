# Decline_StaysInSpace
#// JTL_096 Blue Leader — the "may pay 2" is optional. Declining (AnswerDecision:NO) leaves Blue Leader
#// in the space arena as a plain 3/3 with no Experience.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1SPACEARENACOUNT:1
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:JTL_096
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:POWER:3

---

# MoveAmbush_LukeHealsAttacker
#// JTL_096 Blue Leader + ASH_005 Luke Skywalker (undeployed leader) — full game-2151 chain.
#// P2 plays Blue Leader; Ambush (target = P1 space Chimaera at collection) + When Played (pay 2 → move to
#// ground + 2 XP) both fire. P2 resolves When-Played FIRST → Blue Leader becomes a 5/5 in the ground arena.
#// Ambush re-resolves the moved unit; its only ground target is P1's Thrawn leader-unit (JTL_002 4/7 @ 2
#// dmg), so it auto-fires (single target). Combat: Blue Leader deals 5 → Thrawn (2+5 ≥ 7) defeated; Thrawn
#// deals 4 back → Blue Leader at 4 damage. Then Luke's "when a friendly unit's attack ends" fires: P2
#// exhausts Luke to heal 1 from Blue Leader, leaving it at 3 damage (2 HP remaining).

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
#// Blue Leader moved to P2 ground (idx 1, after Bo-Katan) as a 5/5 and survived the attack …
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:JTL_096
P2GROUNDARENAUNIT:1:POWER:5
P2GROUNDARENAUNIT:1:HP:5
#// … Thrawn defeated by the Ambush attack …
P1GROUNDARENACOUNT:0
#// … and Luke exhausted to heal 1 (4 counter damage − 1 heal = 3).
P2GROUNDARENAUNIT:1:DAMAGE:3
P2LEADER:EXHAUSTED

---

# MoveToGroundFirst_ThenAmbush
#// JTL_096 Blue Leader — Ambush + "When Played: pay 2 → move to the ground arena + 2 Experience" BOTH
#// trigger on play. With an enemy SPACE unit present at collection, Ambush has a target so TWO entry
#// triggers fire and P2 orders them. P2 resolves the When-Played FIRST (Blue Leader → ground as a 5/5),
#// then Ambush must re-resolve the now-moved unit and attack the enemy GROUND unit (JTL_002, a 4/7 leader
#// unit at 2 damage), defeating it.
#//
#// Regression for the stale-mzID fizzle (repro of game 2151): before the fix the Ambush entry-trigger held
#// Blue Leader's original (space) mzID; the When-Played move-to-ground marked that slot removed, so
#// GetZoneObject returned null and Ambush silently fizzled (no attack, enemy leader survived). The fix
#// carries the unit's UID on the Ambush trigger and re-resolves it at dispatch.

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
#// When-Played moved Blue Leader to P2's ground (joining Bo-Katan at idx 0) as a 5/5 (3/3 + 2 Experience) …
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:JTL_096
P2GROUNDARENAUNIT:1:UPGRADECOUNT:2
P2GROUNDARENAUNIT:1:POWER:5
P2GROUNDARENAUNIT:1:HP:5
#// … then Ambush re-resolved the moved unit and defeated the enemy ground leader (JTL_002 4/7 @ 2 dmg).
P1GROUNDARENACOUNT:0

---

# Pay2_MoveToGround_2Exp
#// JTL_096 Blue Leader — Ambush + "When Played: You may pay 2 resources. If you do, move this unit to
#// the ground arena and give 2 Experience tokens to it." Played into an empty enemy board (Ambush has
#// no target → only the WhenPlayed fires); P1 pays 2 and Blue Leader moves to the ground arena as a 5/5
#// (3/3 base + 2 Experience).

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_096}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_096
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
