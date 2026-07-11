# SHD_205 Let the Wookiee Win — "An opponent chooses one: [You ready up to 6 resources] OR [ready a
# friendly unit...]." P1 plays it (cost 2, leaving 4 ready of 6); the opponent picks the ready-resources
# mode, so P1's 2 spent resources are readied back → all 6 ready.

## GIVEN
CommonSetup: yyw/yyw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_205

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:Ready6Resources

## EXPECT
P1RESAVAILABLE:6
