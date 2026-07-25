# Support_DrawIfHealthy
#// ASH_072 Doctor Pershing (Ground, 0/4, Support) — the On Attack "if this unit has 3+ remaining HP, draw
#// a card" is lent to the Support attacker. Pershing is played from hand; the friendly SOR_095 (3/3, 3 HP)
#// is chosen to attack the enemy base, gains the lent On Attack, and — being at 3+ HP — draws a card.
## GIVEN
CommonSetup: bbw/bbk/{myResources:4;handCardIds:ASH_072}
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_164 SOR_232]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1
P2BASEDMG:3

---

# OnAttack_DrawIfHealthy
#// ASH_072 Doctor Pershing (Ground, 0/4) — On Attack: "if this unit has 3 or more remaining HP, draw a
#// card." Pershing (undamaged, 4 HP) attacks the enemy base; being at 3+ HP it draws the top card of the
#// deck. Pershing has 0 power, so the base takes no damage.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_072:1:0
WithP1Deck: [SOR_164 SOR_232]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:1
P2BASEDMG:0

---

# OnAttack_NoDrawWhenBelowThreeHP
#// ASH_072 Doctor Pershing — with 2 damage its remaining HP is 2 (below 3), so the On Attack draw does not
#// happen. Pershing attacks the enemy base; no card is drawn and the deck is untouched.
## GIVEN
CommonSetup: bbw/bbk
WithP1GroundArena: ASH_072:1:2
WithP1Deck: [SOR_164 SOR_232]
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P1HANDCOUNT:0
P1DECKCOUNT:2
P2BASEDMG:0
