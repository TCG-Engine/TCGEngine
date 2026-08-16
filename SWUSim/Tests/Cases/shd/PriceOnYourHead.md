# Bounty_TopDeckToResource
#// SHD_125 Price on Your Head — attached unit gains "Bounty — Put the top card of your deck into
#// play as a resource." P2's marine wears it; LAW_124 defeats the marine; P1 collects: top card of
#// P1's deck becomes an EXHAUSTED resource (no "and ready it" wording).
#// COVERAGE: offer=N/A (the granted Bounty has no target pick — the resource is the collector's own top
#//           deck card) · decline=KNOWN-OPEN (the collect prompt is answered YES in both sections; the
#//           pass branch is not asserted in this file) · control=Bounty_TopDeckToResource
#//           (the upgrade sits on an ENEMY unit and the bounty pays out to the DEFEATING player — P1's
#//           deck and P1's resource row, not the host controller's) · boundary=Bounty_TopDeckToResource
#//           (deck has a card → +1 resource) vs Bounty_EmptyDeck_NoResource (deck empty → collected,
#//           nothing produced) · reqboundary=N/A (the bounty resolves inside the attack, and the
#//           resource-row read happens after the collect answer is serialized)

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_125
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:1
P1RESAVAILABLE:0
P1DECKCOUNT:0

---

# Bounty_EmptyDeck_NoResource
#// SHD_125 Price on Your Head — the Bounty is still collected when the collector's deck is EMPTY, it
#// simply produces nothing. Same kill as the section above but with no cards left in P1's deck: the
#// bounty resolves, P1's resource count stays at zero, and the game moves on cleanly.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_125

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1RESCOUNT:0
P1DECKCOUNT:0
