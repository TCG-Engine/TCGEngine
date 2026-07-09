# SHD_009 Hunter — the return+ramp is gated on the revealed resource sharing a name with a friendly
# UNIQUE unit. P1 controls the unique SOR_179 but reveals a generic (non-Boba-Fett) resource → no name
# match → nothing happens (resource count unchanged, deck untouched, hand empty).

## GIVEN
CommonSetup: yyk/yyk/{myLeader:SHD_009}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_179:1:0
WithP1Resources: 2
WithP1Deck: SOR_095

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:myResources-1

## EXPECT
P1HANDCOUNT:0
P1RESCOUNT:2
P1DECKCOUNT:1
