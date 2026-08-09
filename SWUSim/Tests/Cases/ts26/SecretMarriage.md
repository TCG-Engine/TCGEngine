# FriendlyOnlyNoDraw
#// TS26_46 Secret Marriage — shielding only a friendly unit (no enemy) does NOT draw a card.
## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:TS26_46}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_046 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:0

---

# ShieldEnemyDrawsCard
#// TS26_46 Secret Marriage (Event, cost 2, Vigilance) — Give a Shield to each of up to 2 non-Vehicle
#// units; if you shield an enemy unit this way, draw a card. Shielding one friendly + one enemy shields
#// both and draws 1 (hand 0 after playing the event → 1).
## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:TS26_46}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP1Deck: [SOR_046 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0&theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1HANDCOUNT:1

---

# ShieldingTWOEnemiesStillDrawsOnlyONECard
#// TS26_46 Secret Marriage — "IF you give a Shield to an enemy unit this way, draw A card" is one
#// condition, not one-per-enemy. Both enemy units get their Shield and P1 draws exactly one card.
#// Discriminating: a per-enemy reading would put 2 cards in hand.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:TS26_46}
SkipPreGame: true
P1OnlyActions: true
WithP2GroundArena: [SEC_080:1:0 SOR_095:1:0]
WithP1Deck: [SOR_046 SOR_095 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENAUNIT:1:SHIELDCOUNT:1
P1HANDCOUNT:1

---

# VehiclesAreNotLegalTargets
#// TS26_46 Secret Marriage — "each of up to 2 NON-VEHICLE units". Only SOR_095 is offered; the friendly
#// Vehicle ASH_261 Noti Mobile Pod is not.

## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:TS26_46}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: [SOR_095:1:0 ASH_261:1:0]
WithP1Deck: [SOR_046 SOR_095]

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0
