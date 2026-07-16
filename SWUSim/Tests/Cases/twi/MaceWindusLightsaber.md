# OnMace_Draw2
#// TWI_152 Mace Windu's Lightsaber (Upgrade +?, cost 2, Aggression/Heroism, Lightsaber, non-Vehicle) —
#// "When Played: If attached unit is Mace Windu, draw 2 cards." Attached to SOR_149 (Mace Windu) → draw 2.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:TWI_152}
P1OnlyActions: true
WithP1GroundArena: SOR_149:1:0
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_149
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:2
P1DECKCOUNT:1

---

# OnNonMace_NoDraw
#// TWI_152 Mace Windu's Lightsaber — attached to a non-Mace unit (SOR_095), no cards are drawn.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2;handCardIds:TWI_152}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_046 SOR_046]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1HANDCOUNT:0
