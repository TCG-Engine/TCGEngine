# NameCardBlocksPlay
#// ASH_077 Ryder Azadi (Ground, 2/5) — When Played: name a card; while this unit is in play, opponents
#// can't play cards with that name. P1 plays Ryder and names "Battlefield Marine"; P2 then can't play its
#// SOR_095 (Battlefield Marine) — it stays in hand.
## GIVEN
CommonSetup: bbk/bbw/{myResources:3;handCardIds:ASH_077;theirResources:6;theirHandCardIds:SOR_095}
WithActivePlayer: 1
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P2>PlayHand:0
## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# NamingPlayerCanStillPlay
#// ASH_077 Ryder Azadi — the restriction hits only OPPONENTS; the player who named the card can still play
#// it. P1 plays Ryder, names "Battlefield Marine", then plays its own SOR_095 (Battlefield Marine) — it
#// resolves normally, so P1 ends with 2 units (Ryder + Marine).
## GIVEN
CommonSetup: bbw/bbk/{myResources:9;handCardIds:ASH_077,SOR_095}
WithActivePlayer: 1
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Battlefield Marine
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
