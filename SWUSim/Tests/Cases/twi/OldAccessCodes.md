# NotMoreUnits_NoDraw
#// TWI_168 Old Access Codes — when the opponent does NOT control more units (both control 1), no draw.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:TWI_168}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:0
P1DECKCOUNT:2

---

# OppMoreUnits_Draw
#// TWI_168 Old Access Codes (Upgrade, cost 1, Aggression, Item) — "When Played: If an opponent controls
#// more units than you, draw a card." P2 controls 2 units, P1 controls 1 (its host), so P1 draws.

## GIVEN
CommonSetup: rrk/bbw/{myResources:1;handCardIds:TWI_168}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_095:1:0]
WithP1Deck: [SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:1
P1DECKCOUNT:1
