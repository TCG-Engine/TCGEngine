# Heals3WhenDiscard5Plus
#// TS26_41 Twilight (Unit 3/4 space, cost 3) — When Played: if 5+ cards in your discard, heal 3 from your
#// base. With 5 discarded cards, P1's base damage 3 → 0.
## GIVEN
CommonSetup: bgw/rrk/{myResources:3;myBaseDamage:3;handCardIds:TS26_41;discardCardIds:SEC_080,SOR_095,SOR_046,SOR_128,LAW_180}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:0

---

# NoHealUnderDiscard5
#// TS26_41 Twilight — with only 4 cards in the discard, the "5 or more" condition fails: no heal, P1's
#// base damage stays 3.
## GIVEN
CommonSetup: bgw/rrk/{myResources:3;myBaseDamage:3;handCardIds:TS26_41;discardCardIds:SEC_080,SOR_095,SOR_046,SOR_128}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:3
