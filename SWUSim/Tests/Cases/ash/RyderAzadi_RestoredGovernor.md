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
