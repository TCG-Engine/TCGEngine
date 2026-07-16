# Bounty_TopDeckToResource
#// SHD_116 Outlaw Corona (3-cost 3/5 space) — "Bounty — Put the top card of your deck into play as
#// a resource." Munificent Frigate (JTL_069 4/7) defeats the 1-damaged Corona; P1 collects: the top
#// card of P1's deck becomes a resource, entering EXHAUSTED (no "and ready it" wording — CR default).

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SHD_116:1:1
WithP1Deck: SOR_095

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1RESCOUNT:1
P1RESAVAILABLE:0
P1DECKCOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:3
