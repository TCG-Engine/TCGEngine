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

---

# CountsAUnitYouCONTROLButDoNotOwn
#// COVERAGE: offer=N/A (nothing is targeted — the Experience tokens always go to Chopper himself) ·
#//           reqboundary=N/A (the When Played opens no decision, so no answer crosses a request
#//           boundary) · control=CountsAUnitYouCONTROLButDoNotOwn +
#//           DoesNotCountAUnitYouOWNButTheOpponentControls · boundary=WhenPlayedTwoExpIfCunningVigilance
#//           vs WhenPlayedOneExpNoFriendlyCunningVigilance (qualifying unit present / absent) ·
#//           decline=N/A (mandatory — no "you may").
#// LAW_055 — "if YOU CONTROL a Cunning or Vigilance unit" is a CONTROL test, not an ownership test. Every
#// existing section seats the qualifying unit with owner == controller, so the two are never separable.
#// Here SOR_046 Consular Security Force (Vigilance) sits in P1's ground arena but is OWNED by P2 — P1
#// controls it — so it qualifies and Chopper gets 2 Experience tokens (1/2 -> 3/4). Chopper enters at
#// index 1 behind the seated unit.

## GIVEN
CommonSetup: grw/bgw/{myResources:2}
WithP1GroundArenaControlled: SOR_046:2
WithP1Hand: LAW_055

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:LAW_055
P1GROUNDARENAUNIT:1:UPGRADECOUNT:2
P1GROUNDARENAUNIT:1:POWER:3
P1GROUNDARENAUNIT:1:HP:4

---

# DoesNotCountAUnitYouOWNButTheOpponentControls
#// LAW_055 — the discriminating half, and the one a naive check gets wrong. The same SOR_046 (Vigilance)
#// is OWNED by P1 but sits in P2's ground arena under P2's CONTROL, so P1 controls no Cunning or
#// Vigilance unit and Chopper gets only 1 Experience token (1/2 -> 2/3). A count taken over the units P1
#// OWNS — or a sweep of "the Vigilance units in play" with no controller filter — would have paid out 2.
#// WhenPlayedOneExpNoFriendlyCunningVigilance cannot see this case: its Vigilance unit is both owned AND
#// controlled by the opponent, so owner-scoped and controller-scoped logic agree there.

## GIVEN
CommonSetup: grw/bgw/{myResources:2}
WithP2GroundArenaControlled: SOR_046:1
WithP1Hand: LAW_055

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LAW_055
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:3
