# SmugglePlay_EntersReady
#// SHD_148 Cassian Andor (3-cost 3/5) — "When played using Smuggle: Ready this unit." Smuggled from
#// resources (cost 5, Aggression+Heroism covered by rw leader): he enters play READY instead of the
#// normal exhausted entry.
#// COVERAGE: offer=N/A (the ability has no target — it readies "this unit") · decline=N/A (not a "you may") ·
#//           control=N/A (self-only effect, no other player can be the subject) ·
#//           boundary=SmugglePlay_EntersReady (Smuggle path fires) vs HandPlay_EntersExhausted (hand path
#//           does not) · reqboundary=N/A (the ready lands during the play itself, no cross-request state)

## GIVEN
CommonSetup: rrw/rrw
P1OnlyActions: true
WithP1Resources: 5:SOR_046:1,1:SHD_148:1
WithP1Deck: SOR_095

## WHEN
- P1>SmuggleResource:5

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_148
P1GROUNDARENAUNIT:0:READY
P1RESCOUNT:6
P1DECKCOUNT:0

---

# HandPlay_EntersExhausted
#// Intended: the trigger is gated on "When played USING SMUGGLE". Played the ordinary way out of hand
#// (cost 3, Aggression/Heroism covered by the rw leader) Cassian gets the default exhausted entry and the
#// ready never fires. The resource count is untouched apart from the 3 spent — no Smuggle replacement draw.

## GIVEN
CommonSetup: rrw/rrw/{myResources:5;myhandCardIds:SHD_148}
P1OnlyActions: true
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_148
P1GROUNDARENAUNIT:0:EXHAUSTED
P1RESCOUNT:5
P1DECKCOUNT:1
P1HANDCOUNT:0
