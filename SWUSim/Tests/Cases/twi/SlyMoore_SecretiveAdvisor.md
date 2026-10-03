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

# WhenPlayed_TokenMadeLeaderUnit_IsStolen_PilotStaysAttached
#// TWI_211 Sly Moore — "Take control of an enemy token unit." P2's TIE token carries P2's Pilot leader JTL_008,
#// which makes it a leader unit. Sly Moore still takes it: P1 gains the token, P2's Pilot leader rides along.
#// CR v9.0 3.4.7 (rewritten 2026): "Some abilities make non-leader units leader units ... it doesn't follow
#// rules 3.4.1-3.4.6. ... it can change control or move to an out-of-play zone". A unit made a leader by a
#// Pilot leader is NOT defeated instead (that is 3.4.6, for real leader units). Judges' discussion
#// 2026-10-01: "you're giving control of the unit, and not the leader upgrade" — the Pilot leader stays
#// attached, still controlled by its own player, so that leader stays DEPLOYED. (Before v9 this section
#// asserted the unit was defeated instead.)

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
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_211
P1GROUNDARENAUNIT:1:CARDID:JTL_T01
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P2GROUNDARENACOUNT:0
P2LEADER:DEPLOYED

