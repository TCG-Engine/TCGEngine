# MazKanata_PlayAnotherUnit_Exp
#// SHD_096 Maz Kanata — Unit, cost 1, 1/1, unique, Ground, [Command][Heroism], trait Underworld.
#// "When you play another unit: Give an Experience token to this unit."
#// COVERAGE: offer=N/A (the Experience always goes to Maz herself — "this unit" — so there is no pick) ·
#//           request boundary=N/A (no decision inside the trigger) ·
#//           control=N/A (self-targeting trigger on its own controller's play; a control change would
#//           carry the whole ability with the unit) ·
#//           boundary pair=MazKanata_PlayAnotherUnit_Exp (another unit is played → Experience) +
#//           MazKanata_PlayedWithPiloting_NoExperience (a Piloting play enters as an UPGRADE, not a
#//           unit → nothing) ·
#//           decline=N/A (mandatory, no "you may").
#// Maz (1/1) is in play; playing another unit (SOR_095) gives Maz an Experience token → 2/2.

## GIVEN
CommonSetup: ggw/ggw/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SHD_096:1:0
WithP1Hand: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2

---

# MazKanata_PlayedWithPiloting_NoExperience
#// SHD_096 Maz Kanata — the trigger is "when you play another UNIT". A card played for its Piloting cost
#// enters play as an UPGRADE on a Vehicle, not as a unit, so Maz gets nothing. P1 plays JTL_108 Clone
#// Pilot (Command, Piloting [2 resources Command] — the g base covers Command) onto its SOR_225, a
#// Vehicle with no Pilot: the pilot lands as an upgrade on SOR_225 and Maz stays a bare, unupgraded 1/1.

## GIVEN
CommonSetup: ggw/ggw/{myResources:6}
P1OnlyActions: true
WithP1GroundArena: SHD_096:1:0
WithP1SpaceArena: SOR_225:1:0
WithP1Hand: JTL_108

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_096
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_108
