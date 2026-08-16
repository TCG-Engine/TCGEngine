# RevealNonUnit_GiveExp
#// SHD_057 Rickety Quadjumper (2-cost, Vigilance) — "On Attack: You may reveal the top card of your deck.
#// If it's not a unit, give an Experience token to another unit. (Leave it on top.)" Top card is the event
#// SOR_251 (not a unit) → the friendly SOR_046 gets an Experience token, and the deck is unchanged (2 cards).
#// COVERAGE: offer=the Experience target auto-resolves in RevealNonUnit_GiveExp because "another unit"
#//           excludes Rickety herself, leaving exactly one legal unit — that auto-resolution IS the
#//           self-exclusion assertion; EmptyDeck_RevealIsASafeNoOp is the degenerate end (the reveal is
#//           offered but has nothing to reveal, so no target prompt follows) ·
#//           decline=N/A as a distinct outcome — the optional reveal is
#//           answered YES in both reveal sections, and declining it produces a board state that is
#//           indistinguishable from RevealUnit_NoExp (no Experience given, deck untouched, attacker
#//           exhausted), so no separate section would assert anything new ·
#//           boundary=RevealNonUnit_GiveExp (non-unit on top → token) vs RevealUnit_NoExp (unit on top →
#//           no token) vs EmptyDeck_RevealIsASafeNoOp (no top card at all → no token, deck untouched) ·
#//           control=N/A (the token goes to a unit chosen at resolution; nothing is stored against a
#//           controller) · reqboundary=N/A (the reveal and the grant resolve inside the one attack; the
#//           card is left on top of the deck rather than held in any staging zone)

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_057:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Deck: [SOR_251 SOR_095]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:UPGRADECOUNT:1
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_251

---

# RevealUnit_NoExp
#// SHD_057 Rickety Quadjumper — when the revealed top card IS a unit (SOR_095), no Experience is given. The
#// deck is unchanged (card left on top).

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_057:1:0
WithP1GroundArena: SOR_046:1:0
WithP1Deck: [SOR_095 SOR_251]

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
P1DECKCOUNT:2
P1DECKTOPCARD:SOR_095

---

# EmptyDeck_RevealIsASafeNoOp
#// SHD_057 Rickety Quadjumper — the empty-deck bound. With no cards left there is no top card to reveal,
#// so nothing can satisfy "if it's not a unit": accepting the optional reveal is a safe no-op. No
#// Experience token is given, the deck stays empty (nothing is drawn or milled trying to produce a card
#// to reveal), no follow-up target prompt is raised, and the attack still lands Rickety's 1 damage.
#// Intended: with no card to reveal the choice is immaterial, so SWUSim offering the YES/NO anyway is a
#// harmless prompt difference — what this section pins down is that taking it changes nothing.

## GIVEN
CommonSetup: bbw/bbw
P1OnlyActions: true
WithP1GroundArena: SHD_057:1:0
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1NODECISION
P1DECKCOUNT:0
P2BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:SHD_057
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:1:CARDID:SOR_046
P1GROUNDARENAUNIT:1:UPGRADECOUNT:0
