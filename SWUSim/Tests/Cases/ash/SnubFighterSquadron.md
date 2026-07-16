# WhenPlayedDealSpaceUnit
#// ASH_194 Snub Fighter Squadron (Space, 4/3, Ambush) — When Played: deal 1 damage to a space unit. Two
#// entry triggers (Ambush + When Played); resolving the When Played (EffectStack-0) first deals 1 to
#// SOR_225 (2/1), defeating it — the Ambush then has no enemy unit to attack and is skipped.
## GIVEN
CommonSetup: yyk/yyk/{myResources:4;handCardIds:ASH_194}
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:ASH_194
