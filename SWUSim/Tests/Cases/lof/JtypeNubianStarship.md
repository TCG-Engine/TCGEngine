# WhenDefeated_Discard
#// LOF_194 J-Type Nubian Starship — When Defeated: discard a card from your hand. It attacks a 4/7, dies,
#// and P1 discards its only hand card.

## GIVEN
CommonSetup: yyw/rrk/{handCardIds:SOR_095}
P1OnlyActions: true
WithP1SpaceArena: LOF_194:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P1HANDCOUNT:0
P1SPACEARENACOUNT:0

---

# WhenDefeated_ChooseFromTwo
#// LOF_194 J-Type Nubian Starship — When Defeated: discard a card from your hand. With TWO cards in hand the
#// discard is a mandatory choice — both hand cards are selectable and there is no way to decline. It attacks
#// a 5/6 (JTL_069), dies, and P1 must pick one of the two to discard — both hand cards are the only
#// selectable options, with no pass / no choose-nothing button.

## GIVEN
CommonSetup: yyw/rrk/{handCardIds:SOR_095,SOR_046}
P1OnlyActions: true
WithP1SpaceArena: LOF_194:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P1SELECTABLEEXACT:myHand-0&myHand-1

---

# WhenPlayed_Draw
#// LOF_194 J-Type Nubian Starship — When Played: draw a card. P1 plays it and draws.

## GIVEN
CommonSetup: yyw/rrk/{myResources:3;handCardIds:LOF_194}
P1OnlyActions: true
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:1
