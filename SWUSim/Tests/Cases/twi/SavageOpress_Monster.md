# NoReady_EqualUnits
#// TWI_137 Savage Opress WhenPlayed — does NOT ready self when unit counts are equal.
#// P2 has 1 unit; after playing Savage, P1 also has 1 unit. Condition not met; Savage stays exhausted.

## GIVEN
CommonSetup: rrk/grw/{myResources:7;handCardIds:TWI_137}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# ReadiesSelf
#// TWI_137 Savage Opress WhenPlayed — readies self when P1 has fewer units than P2.
#// P2 has 2 units; after playing Savage, P1 has 1 unit < P2's 2. Trigger fires; Savage is readied.
#// Units enter play exhausted (Status=0); the self-ready makes Savage ready.

## GIVEN
CommonSetup: rrk/grw/{myResources:7;handCardIds:TWI_137}
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:READY

---

# EnemyDeployedLeaderIsAUnit_Readies
#// "fewer UNITS" — a deployed leader is a unit. P2 has its deployed leader plus one Battlefield Marine (2
#// units); P1 plays Savage (1). 1 < 2 → ready. The count used NonLeaderUnitFilter, so P2 read as 1 unit and
#// Savage stayed exhausted.

## GIVEN
CommonSetup: rrk/grw/{myResources:7;handCardIds:TWI_137;theirLeaderDeployed:true}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:TWI_137
P1GROUNDARENAUNIT:0:READY

---

# FriendlyDeployedLeaderIsAUnitToo_NoReady
#// Both sides count leaders: P1 has a deployed leader too, so after playing Savage P1 controls 2 units —
#// not fewer than P2's 2 (deployed leader + Marine). No ready.

## GIVEN
CommonSetup: rrk/grw/{myResources:7;handCardIds:TWI_137;myLeaderDeployed:true;theirLeaderDeployed:true}
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_137
P1GROUNDARENAUNIT:1:EXHAUSTED

---

# FourSeats_AnOpponent_IsOneOpponentNotTheirSum
#// "fewer units than AN opponent" compares against EACH opponent, never their total. P2 and P3 each control
#// one unit; P1 plays Savage (1 unit). 1 is not fewer than either → no ready. The count summed every
#// opponent's arena (the Twin Suns fan-out of their*), read 1 < 2, and readied.

## GIVEN
CommonSetup4P: rrk/grw/grw/grw/{myResources:7;handCardIds:TWI_137}
SkipPreGame: true
WithActivePlayer: 1
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:TWI_137
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# FourSeats_LiveBoard_FarSeatWithTokenAndDeployedLeader_Readies
#// Reported 2026-09-30 (game 1438045, 4 seats). P4 plays Savage with no other units (1). P1 controls a
#// Mandalorian token and its deployed leader (2 units); P3 controls only its deployed leader (1). 1 < P1's 2
#// → ready. Live it stayed exhausted: leaders were not counted, so P1 read as 1, and 1 < 1 is false.

## GIVEN
CommonSetup4P: rrk/grw/grw/rrk/{myLeaderDeployed:true}
SkipPreGame: true
WithActivePlayer: 4
WithP1GroundArena: ASH_T01:1:0
WithP4Resources: 9
WithP4Hand: TWI_137

## WHEN
- P4>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P4GROUNDARENAUNIT:0:CARDID:TWI_137
P4GROUNDARENAUNIT:0:READY

---

# TeamSuns_TeammateIsNotAnOpponent_EachOpponentEqual_NoReady
#// Owner scenario (2026-09-30). Team Suns: P1 + P3 vs P2 + P4. P1 plays Savage as its ONLY unit (1). The
#// teammate P3 controls 3 units; each opponent (P2, P4) controls 1. "Fewer units than AN OPPONENT": a
#// teammate is not an opponent, and the opponents are compared ONE AT A TIME — 1 is not fewer than 1 for
#// either, so Savage stays exhausted. Two wrong readings both READY here: counting the teammate (1 < 3) and
#// summing the opponents (1 < 1+1).

## GIVEN
CommonSetup: rrk/grw/{myResources:7;handCardIds:TWI_137}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP2GroundArena: SOR_095:1:0
WithP3GroundArena: [SOR_095:1:0 SOR_095:1:0 SOR_095:1:0]
WithP4GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:3
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_137
P1GROUNDARENAUNIT:0:EXHAUSTED
