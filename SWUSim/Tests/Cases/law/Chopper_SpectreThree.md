# WhenPlayedTwoExpIfCunningVigilance
#// LAW_055 Chopper (1/2, Raid 1) — When Played: give an Experience token to this unit (2 instead if you
#// control a Cunning or Vigilance unit). P1 controls SOR_063 (Vigilance) -> 2 Experience (1/2 -> 3/4).

## GIVEN
CommonSetup: grw/bgw/{myResources:2}
WithP1GroundArena: SOR_063:1:0
WithP1Hand: LAW_055

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_055
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:4

---

# WhenPlayedOneExpNoFriendlyCunningVigilance
#// LAW_055 Chopper — When Played: only 1 Experience when NO friendly Cunning/Vigilance unit. Friendly
#// SOR_095 (Command). Enemy Vigilance (SOR_046) + enemy Cunning (SOR_178) do NOT count -> 1 Exp (1/2 -> 2/3).

## GIVEN
CommonSetup: grw/bgw/{myResources:2}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_178:1:0
WithP1Hand: LAW_055

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_055
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1GROUNDARENAUNIT:1:POWER:2
P1GROUNDARENAUNIT:1:HP:3

---

# WhenPlayedTwoExpBothCunningAndVigilance
#// LAW_055 Chopper — When Played: 2 Experience (capped) when controlling BOTH a Vigilance (SOR_046) and a
#// Cunning (SOR_178) friendly unit -> 2 Exp (1/2 -> 3/4).

## GIVEN
CommonSetup: grw/bgw/{myResources:2}
WithP1GroundArena: SOR_046:1:0
WithP1SpaceArena: SOR_178:1:0
WithP1Hand: LAW_055

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_055
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:4

---

# WhenPlayedTwoExpOnlyCunning
#// LAW_055 Chopper — When Played: 2 Experience with only a friendly Cunning unit (SOR_178, space) ->
#// 2 Exp (1/2 -> 3/4). Chopper is the only ground unit (index 0).

## GIVEN
CommonSetup: grw/bgw/{myResources:2}
WithP1SpaceArena: SOR_178:1:0
WithP1Hand: LAW_055

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_055
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:4
