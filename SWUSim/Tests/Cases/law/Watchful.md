# OnAttackScryBottom
#// LAW_125 Watchful (Upgrade) — grants "On Attack: Look at the top card of a deck. You may put it on the
#// bottom of that deck." SEC_080 wears Watchful and attacks the base. Only P1 has a deck here, so the
#// deck-choice step is skipped (auto-picks P1's deck); P1 looks at the top (SOR_046) and bottoms it, so
#// the new top is SOR_095.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_125
WithP1Deck: SOR_046
WithP1Deck: SOR_095

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Bottom

## EXPECT
P1DECKTOPCARD:SOR_095
P1DECKCOUNT:2

---

# OnAttackScryOwnTop
#// LAW_125 Watchful — choosing your own deck and putting the looked-at card back on TOP leaves the deck
#// unchanged. SEC_080 wears Watchful and attacks the base; P1 looks at their top card (SOR_046) and keeps
#// it on top, so the deck order is untouched.

## GIVEN
CommonSetup: rrk/rrk/{}
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_125
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP2Deck: SOR_128
WithP2Deck: SOR_164

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Yours
- P1>AnswerDecision:Top

## EXPECT
P1DECKTOPCARD:SOR_046
P1DECKCOUNT:2

---

# OnAttackScryOpponentTop
#// LAW_125 Watchful — you may look at the OPPONENT's deck instead. Choosing their deck and keeping the top
#// card (SOR_128) on top leaves their deck unchanged.

## GIVEN
CommonSetup: rrk/rrk/{}
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_125
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP2Deck: SOR_128
WithP2Deck: SOR_164

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Theirs
- P1>AnswerDecision:Top

## EXPECT
P2DECKTOPCARD:SOR_128
P2DECKCOUNT:2

---

# OnAttackScryOpponentBottom
#// LAW_125 Watchful — choosing the opponent's deck and bottoming their top card (SOR_128) makes their new
#// top the next card (SOR_164).

## GIVEN
CommonSetup: rrk/rrk/{}
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_125
WithP1Deck: SOR_046
WithP1Deck: SOR_095
WithP2Deck: SOR_128
WithP2Deck: SOR_164

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Theirs
- P1>AnswerDecision:Bottom

## EXPECT
P2DECKTOPCARD:SOR_164
P2DECKCOUNT:2

---

# OnAttackEmptyDecksNoEffect
#// LAW_125 Watchful — with both decks empty there is nothing to look at, so no scry prompt appears; the
#// attack simply resolves.

## GIVEN
CommonSetup: rrk/rrk/{}
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_125

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1DECKCOUNT:0
P2DECKCOUNT:0
