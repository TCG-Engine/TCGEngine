# DefeatUpgradedUnit
#// ASH_067 Get Lost (Event, cost 4) — Defeat an upgraded non-leader unit. The enemy SEC_080 carries SOR_120
#// (the only upgraded unit, auto-resolved) and is defeated.
## GIVEN
CommonSetup: bbw/bbk/{myResources:4;handCardIds:ASH_067}
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0

---

# NoUpgradedUnit_Fizzles
#// ASH_067 Get Lost — only an UPGRADED unit can be defeated. With the enemy SEC_080 carrying no upgrade,
#// Get Lost has no legal target and fizzles (SEC_080 survives).
## GIVEN
CommonSetup: bbw/bbk/{myResources:4;handCardIds:ASH_067}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:1

---

# Offer_UpgradedNonLeader_SpansBOTHSides_ExcludesBareUnitsAndLeaderUnits
#// THE OFFER CELL. Every other section here answers a target, which proves the BRANCH and never the
#// POOL - and this card carries THREE restrictions at once, two printed and one absent:
#//   · "UPGRADED"   - a bare unit is out (P2's Stormtrooper);
#//   · "NON-LEADER" - a deployed leader is out EVEN WHEN UPGRADED, which is the sharpest exclusion
#//                    because such a leader satisfies the only printed adjective;
#//   · no controller word at all - so it spans BOTH SIDES and P1's own upgraded unit is a legal
#//                    (self-destructive) target.
#// Two legal targets, so nothing auto-resolves and there is a real pool to read.
#// ⚠ A deployed leader is appended LAST to the ground arena, so P2 reads [SEC_080, SOR_128, leader].

## GIVEN
CommonSetup: bbw/rrk/{theirLeaderDeployed:true}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: [ASH_067]
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 2:SOR_120

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&theirGroundArena-0
