# Deal2ToBaseControllerDraws
#// TS26_62 R2-D2 (Unit 1/3, cost 2) — Raid 2 + When Played: you may deal 2 damage to a base; if you do,
#// that base's controller draws a card. Dealing 2 to the enemy base makes P2 (its controller) draw.
## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:TS26_62}
WithP2Deck: [SEC_080 SOR_095]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:2
P2HANDCOUNT:1

---

# TheDamageCanBeSkipped
#// TS26_62 R2-D2 — "You MAY deal 2 damage to a base." Declining leaves P2's base untouched and gives them
#// no card; R2-D2 still enters play.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:TS26_62}
SkipPreGame: true
P1OnlyActions: true
WithP2Deck: [SEC_080 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:0
P2HANDCOUNT:0
P1GROUNDARENACOUNT:1

---

# TargetingYourOWNBaseMakesYOUDraw
#// TS26_62 R2-D2 — "that base's CONTROLLER draws a card", so aiming at your own base is a deliberate way
#// to draw: P1's base takes the 2 and P1 is the one who draws. P2 gets nothing.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:TS26_62}
SkipPreGame: true
P1OnlyActions: true
WithP1Deck: [SEC_080 SOR_095]
WithP2Deck: [SEC_080 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:2
P1HANDCOUNT:1
P2HANDCOUNT:0

---

# NoDrawWhenTheDamageIsPrevented
#// TS26_62 R2-D2 — "deal 2 damage to a base. IF YOU DO, that base's controller draws a card." P2 plays
#// Close the Shield Gate (JTL_074) on their own base first, so R2-D2's 2 damage is prevented: their base
#// stays on 0 and they do NOT draw, leaving their hand empty.
#// Discriminating: the draw used to fire on the attempt, handing the opponent a free card.

## GIVEN
CommonSetup: rrw/bbw/{myResources:2;handCardIds:TS26_62;theirResources:3}
SkipPreGame: true
WithActivePlayer: 2
WithP2Hand: JTL_074
WithP1Deck: [SEC_080 SOR_095]
WithP2Deck: [SEC_080 SOR_095]

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myBase-0
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:0
P2HANDCOUNT:0
