# ASH_148 Ninth Sister — When Played with the opponent holding NO cards: there is nothing to discard,
# so the whole "discard → deal damage" rider cleanly fizzles (no discard, no damage, no pending decision).
# The unit itself still enters play.
## GIVEN
CommonSetup: rrk/rrk/{
  myResources:7;
  myhandCardIds:ASH_148
}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_148
P2HANDCOUNT:0
P2DISCARDCOUNT:0
P1NODECISION
