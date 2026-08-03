# WhenPlayed_StealToken
#// TWI_211 Sly Moore (Unit 3/3, Ground, cost 3, Republic/Official) — "When Played: Take control of an enemy
#// token unit and ready it." P1 takes control of P2's Battle Droid token (TWI_T01), readying it under P1.

## GIVEN
CommonSetup: yyk/bbw/{myResources:3;handCardIds:TWI_211}
P1OnlyActions: true
WithP2GroundArena: TWI_T01:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:TWI_T01
P1GROUNDARENAUNIT:1:READY

---

# WhenPlayed_TokenIsLeaderUnit_DefeatedInsteadOfStolen
#// TWI_211 Sly Moore — "Take control of an enemy token unit." Her target filter has no "non-leader"
#// qualifier, so an enemy TOKEN that has been made a LEADER UNIT (its controller deployed a Pilot leader
#// onto it) is still a legal target — but the take-control cannot happen. CR 3.4.6: "If an ability would
#// cause a Leader Unit to move to an out-of-play zone or change control for any reason, it is DEFEATED
#// instead." So the token is defeated rather than stolen, and per CR 3.4.5 the Leader Upgrade flips back
#// to its owner's leader zone EXHAUSTED. P2 ends with an empty ground arena and an undeployed, exhausted
#// leader; P1 keeps only Sly Moore herself — she gains nothing.
#// ⚠ This section deliberately does NOT assert P2's discard: SWUSim currently sends a defeated TOKEN to
#// the discard pile instead of having it cease to exist. That is a separate, PRE-EXISTING issue (a plain
#// Vanquish on a token behaves the same way), independent of the leader-unit rule proved here.
#// REGRESSION GUARD (engine, 2026-08-02): SWUTakeControlOfUnit transferred leader units outright, which
#// handed a player a unit still carrying the OPPONENT's leader — an illegal state. The guard now lives at
#// that shared chokepoint, so it covers every take-control caller AND the return half of a temporary
#// steal (see LiberatedByDarkness::StolenUnitBecomesLeaderUnit_DefeatedAtRegroup).

## GIVEN
CommonSetup: yyk/bbw/{
  myResources:3;
  handCardIds:TWI_211;
  theirLeader:JTL_008;
  theirLeaderDeployedPilot:true
}
P1OnlyActions: true
WithP2GroundArena: JTL_T01:0:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_211
P2GROUNDARENACOUNT:0
P2LEADER:NOTDEPLOYED
P2LEADER:EXHAUSTED
