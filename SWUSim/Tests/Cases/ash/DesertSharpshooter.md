# DealTwoUpgradedGround
#// ASH_170 Desert Sharpshooter (Ground, 3/3, cost 3) — When Played: you may deal 2 damage to an upgraded
#// ground unit. P1 targets the only upgraded ground unit, SEC_080 (3/3 + SOR_120), dealing 2.
## GIVEN
CommonSetup: rrk/rrk/{myResources:3;handCardIds:ASH_170}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Offer_UpgradedGroundUnit_SpansBOTHSides_ExcludesBareUnitsAndTheSpaceArena
#// THE OFFER CELL, and this card's two restrictions pull in different directions:
#//   · "UPGRADED"  - a bare unit is out;
#//   · "GROUND"    - an upgraded SPACE unit is out, which is the exclusion a pool built from
#//                   SWUAllUnits without an arena argument would silently admit;
#//   · no controller word - so it spans BOTH SIDES and P1's own upgraded unit is legal.
#// The Sharpshooter herself enters bare, so she is excluded by the upgraded filter rather than by any
#// "another" clause - the text has none, so an upgraded copy of her WOULD be a legal self-target.
#// Two legal targets keeps the pick from auto-resolving.
#// ⚠ She is PLAYED, so she lands after P1's seeded unit: P1's ground reads [SOR_046, Sharpshooter].

## GIVEN
CommonSetup: rrk/rrk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 3
WithP1Hand: [ASH_170]
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
