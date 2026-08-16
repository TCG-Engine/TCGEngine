# AnotherCunning_GainsShielded
#// SHD_212 Privateer Scyk — Unit, cost 2, 2/2, Space, [Cunning], traits Fringe/Vehicle/Fighter.
#// "While you control another Cunning unit, this unit gains Shielded."
#// COVERAGE: offer=N/A (a static conditional keyword grant has no target pick) ·
#//           request boundary=N/A (no decision inside the grant) ·
#//           control=NoFriendlyCunningUnit_NoShielded — the enabling Cunning unit sits on the ENEMY side
#//           there, which is precisely the "you control" half of the condition ·
#//           boundary pair=AnotherCunning_GainsShielded (another friendly Cunning unit → Shielded) +
#//           NoFriendlyCunningUnit_NoShielded (none → NOTKEYWORD; also the "does not count itself"
#//           negative, since Privateer Scyk is itself Cunning) ·
#//           decline=N/A (no "you may").
#// Guard: with another friendly Cunning unit (SHD_186) in play, it has Shielded.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: SHD_212:1:0
WithP1GroundArena: SHD_186:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SHD_212
P1SPACEARENAUNIT:0:HASKEYWORD:Shielded

---

# NoFriendlyCunningUnit_NoShielded
#// SHD_212 Privateer Scyk — the grant needs ANOTHER Cunning unit that YOU CONTROL. The only other Cunning
#// unit here (SHD_186) belongs to P2, and Privateer Scyk — Cunning itself — does not count itself, so the
#// condition is false and it has no Shielded.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1SpaceArena: SHD_212:1:0
WithP2GroundArena: SHD_186:1:0

## WHEN
- P1>Pass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SHD_212
P1SPACEARENAUNIT:0:NOTKEYWORD:Shielded
