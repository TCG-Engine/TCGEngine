# AttachTakeControl
#// JTL_083 Pantoran Starship Thief — "When Played: You may pay 3 resources. If you do, attach this unit as
#// an upgrade to a Fighter or Transport unit without a Pilot on it. Take control of that unit." Played as a
#// unit (no friendly Vehicle), P1 pays 3 and attaches onto the enemy SOR_237, taking control of it — the
#// X-Wing moves into P1's space arena with the Thief as a pilot upgrade.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8;handCardIds:JTL_083}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_083

---

# DetachReturnsControl
#// JTL_083 Pantoran Starship Thief — "When this upgrade detaches from a unit: That unit's owner takes
#// control of it." P1 attaches the Thief to the enemy SOR_237 and takes control; then P1 plays System
#// Shock (JTL_175) to defeat the Thief upgrade — SOR_237 returns to P2's control.

## GIVEN
CommonSetup: ggk/rrk/{myResources:10;handCardIds:JTL_083}
P1OnlyActions: true
WithP1Hand: JTL_175
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>PlayHand:0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# DeclinePay_EntersAsUnit
#// JTL_083 Pantoran Starship Thief — the "You may pay 3 resources" is optional. P1 declines (Pass): the
#// Thief simply enters P1's ground arena as a normal 2/2 unit and no enemy unit is stolen.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8;handCardIds:JTL_083}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_083
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237

---

# CannotTargetPilotedVehicle
#// JTL_083 Pantoran Starship Thief — the attach target must be a Fighter/Transport WITHOUT a Pilot. The
#// enemy SOR_237 already carries a pilot (JTL_046), so it is not eligible: with no legal target the Thief
#// just enters P1's ground arena as a unit and SOR_237 stays under P2's control.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8;handCardIds:JTL_083}
P1OnlyActions: true
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArenaUpgrade: 0:JTL_046

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_083
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_237

---

# AttachEnemyTransport_TakeControl_Costs
#// JTL_083 Pantoran Starship Thief — the attach target may be a TRANSPORT (not only a Fighter), and the
#// player playing the card pays all costs. P1 (8 resources) plays the 2-cost Thief and pays the 3-resource
#// ability (5 exhausted → 3 left), attaching onto the enemy Transport SOR_250 and taking control: the
#// Corellian Freighter moves into P1's space arena with the Thief as its upgrade.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8;handCardIds:JTL_083}
P1OnlyActions: true
WithP2SpaceArena: SOR_250:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_250
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_083
P1RESAVAILABLE:3

---

# AttachFriendly_NoControlChange
#// JTL_083 Pantoran Starship Thief — it can attach to a FRIENDLY Fighter/Transport without a Pilot too. P1
#// pays 3 and attaches the Thief onto its own SOR_237; the X-Wing stays under P1's control (no control
#// change) and carries the Thief as an upgrade, so P1 has one space unit and nothing in the ground arena.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8;handCardIds:JTL_083}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_083
