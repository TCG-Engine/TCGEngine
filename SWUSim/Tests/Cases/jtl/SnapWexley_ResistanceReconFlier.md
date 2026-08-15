# AsUnit_NextResistanceDiscount
#// JTL_098 Snap Wexley — "When played as a unit/On Attack: The next Resistance card you play this phase
#// costs 1 resource less." Played as a unit (no friendly Vehicle → no Pilot option), then P1 plays the
#// Resistance unit JTL_099 (cost 3) which costs 2 thanks to the discount. Resource check: 10 − 3 (Snap)
#// − 2 (discounted JTL_099) = 5 ready left (would be 4 without the discount).

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;handCardIds:JTL_098}
P1OnlyActions: true
WithP1Hand: JTL_099

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:5

---

# AsUpgrade_SearchResistance
#// JTL_098 Snap Wexley — Piloting + "When played as an upgrade: Search the top 5 cards of your deck for a
#// Resistance card, reveal it, and draw it." Played as a Pilot onto SOR_237, P1 searches the top 5 (only
#// JTL_099 is a Resistance card) and draws it.

## GIVEN
CommonSetup: ggw/rrk/{myResources:8;handCardIds:JTL_098}
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1Deck: JTL_099
WithP1Deck: SEC_080
WithP1Deck: SOR_128
WithP1Deck: SOR_225
WithP1Deck: SOR_046

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:JTL_099

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:1

---

# OnAttack_NextResistanceDiscount
#// JTL_098 Snap Wexley — the discount also fires on the "On Attack" half. Seated as a unit (ready), Snap
#// attacks the base; then P1 plays the Resistance unit JTL_099 (cost 3) for 2. Resources: 5 − 2 = 3 left.

## GIVEN
CommonSetup: ggw/rrk/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: JTL_098:1:0
WithP1Hand: JTL_099

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:3

---

# BB8ForZero_WithZeroResources
#// JTL_098 Snap Wexley — played as a unit, his "next Resistance card costs 1 less" discount can bring a
#// 1-cost card to 0 and it stays playable with 0 ready resources. Snap (cost 3) is played with exactly 3
#// resources → 0 ready; then BB-8 (JTL_145, Resistance, cost 1) is discounted to 0 and still enters play.
#// (Aspect rgw covers Aggression+Command+Heroism so neither play takes an off-aspect penalty.)

## GIVEN
CommonSetup: rgw/rrk/{myResources:3;handCardIds:JTL_098}
P1OnlyActions: true
WithP1Hand: JTL_145

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:0

---

# SimulateRequestBoundary_DiscountChargeSurvives
#// JTL_098 Snap Wexley — "the next Resistance card you play this phase costs 1 less" is written by one
#// player action and consumed by the NEXT one, so in production the charge is written in one process and
#// read in a fresh one. Mirrors AsUnit_NextResistanceDiscount with the boundary between the two plays:
#// JTL_099 must still cost 2 instead of 3, leaving 10 − 3 − 2 = 5 ready resources.

## GIVEN
CommonSetup: ggw/rrk/{myResources:10;handCardIds:JTL_098}
P1OnlyActions: true
WithP1Hand: JTL_099

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1RESAVAILABLE:5

---

# ControlChange_DiscountGoesToTheNewController
#// JTL_098 Snap Wexley — "On Attack: The next Resistance card YOU play this phase costs 1 resource less."
#// Under SOR_224 Change of Heart the NEW controller is the one attacking, so the discount charge belongs to
#// the THIEF, not to Snap's owner. P1 steals Snap (P2's SOR_046 keeps the take-control choose interactive),
#// P2 attacks with SOR_046, then P1 attacks with Snap to arm the discount. The other half is asserted too:
#// P2 plays JTL_099 Veteran Fleet Officer (Resistance, cost 3) NEXT and must pay the FULL 3 (3 - 3 = 0
#// ready), then P1 plays the same card for 2 (9 - 6 Change of Heart - 2 = 1 ready).

## GIVEN
CommonSetup: ygw/ggw
SkipPreGame: true
WithP1Resources: 9
WithP2Resources: 3
WithP1Hand: SOR_224
WithP1Hand: JTL_099
WithP2Hand: JTL_099
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]
WithP2GroundArena: JTL_098:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AttackGroundArena:0:BASE
- P1>AttackGroundArena:0:BASE
- P2>PlayHand:0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:JTL_098
P2GROUNDARENACOUNT:2
P1RESAVAILABLE:1
P2RESAVAILABLE:0
