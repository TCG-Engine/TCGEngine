# NoOnAttack_JustAttacks
#// JTL_174 Hotshot Maneuver — "Choose a friendly unit. For each of its 'On Attack' abilities, deal 2
#// damage to a different enemy unit. Then, attack with the chosen unit." The chosen unit JTL_249
#// (Millennium Falcon, 3 power) has NO On Attack ability, so no damage is dealt; it just attacks the
#// P2 base for 3.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1SpaceArena: JTL_249:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:3
P1NODECISION

---

# OneOnAttack_Deal2ThenAttack
#// JTL_174 Hotshot Maneuver — the chosen unit JTL_243 (Quasar TIE Carrier, 5 power) has ONE On Attack
#// ability ("create a TIE"), so P1 deals 2 to one enemy unit (SOR_225, 2/1 → dies), THEN attacks with
#// JTL_243: its On Attack creates a TIE token and, with no enemy units left, it hits the P2 base for 5.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1SpaceArena: JTL_243:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:5
P1SPACEARENACOUNT:2

---

# OpponentControlsNoUnits_WhiffsThenAttacksBase
#// JTL_174 Hotshot Maneuver — when the opponent controls no units, the "deal 2 to a different enemy unit"
#// per On Attack ability has no target and is skipped (no prompt), but the chosen unit still attacks. JTL_243
#// (Quasar TIE Carrier, 5 power, one On Attack = create a TIE) attacks P2's base for 5 and its On Attack
#// makes a TIE token → arena has JTL_243 + the TIE.

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP1SpaceArena: JTL_243:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:5
P1SPACEARENACOUNT:2
P1NODECISION

---

# PlayedWithNoValidTarget
#// JTL_174 Hotshot Maneuver requires a friendly unit to choose; with none in play the event resolves with
#// no effect and goes to discard (the enemy unit is untouched, no attack happens).

## GIVEN
CommonSetup: rrw/rrk/{myResources:8;handCardIds:JTL_174}
P1OnlyActions: true
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_174
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:DAMAGE:0
P2BASEDMG:0
