# WhenPlayed_ExpMandalorian
#// SHD_258 Mandalorian Warrior (3-cost ground) — "When Played: You may give an Experience token to another
#// Mandalorian unit." P1 gives the token to the friendly Mandalorian SHD_150.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_258
WithP1GroundArena: SHD_150:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_150
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# WhenPlayed_ExpFoundlingMandalorian
#// SHD_258 Mandalorian Warrior — object-aware trait check: a vanilla non-Mandalorian unit (SOR_046)
#// wearing SHD_069 Foundling counts as a Mandalorian, so it is a legal "another Mandalorian unit" target
#// and receives the Experience upgrade. (Before routing SHD_258's filter through TraitContains this unit
#// was invisible — printed HasTrait missed the Foundling grant.)

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1Hand: SHD_258
WithP1GroundArena: SOR_046:1:0
WithP1GroundArenaUpgrade: 0:SHD_069

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
