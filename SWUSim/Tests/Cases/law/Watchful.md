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
- P1>AnswerDecision:Your_deck
- P1>AnswerDecision:Leave

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
- P1>AnswerDecision:Opponent's_deck
- P1>AnswerDecision:Leave

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
- P1>AnswerDecision:Opponent's_deck
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

---

# DeckChoiceOffersBOTHDecks
#// LAW_125 Watchful — "Look at the top card of A DECK" names no owner, so the first decision is a genuine
#// choice between the two decks. The four existing sections each ANSWER that choice (Yours or Theirs) and
#// so would pass unchanged against an implementation that offered only one of them; this one leaves the
#// decision pending and asserts both options are actually on it.

## GIVEN
CommonSetup: rrk/rrk/{}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_125
WithP1Deck: [SOR_046 SOR_095]
WithP2Deck: [SOR_128 SOR_164]

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1OPTIONHAS:Your_deck
P1OPTIONHAS:Opponent's_deck

---

# UpgradeRemoved_NoScryAtAll
#// LAW_125 Watchful — the On Attack belongs to the upgrade's grant, so taking the upgrade away takes the
#// scry with it. P1 Confiscates its own Watchful and then attacks: no decision is raised and both decks
#// are left in their original order. Without this negative a grant registered once on the host and never
#// revoked would look identical in every other section here.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:LAW_125
WithP1Deck: [SOR_046 SOR_095]
WithP2Deck: [SOR_128 SOR_164]
WithP1Hand: SOR_251

## WHEN
- P1>PlayHand:0
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1DECKTOPCARD:SOR_046
P2DECKTOPCARD:SOR_128

---

# AttachPool_AnyUnitEitherSideEitherArena
#// LAW_125 Watchful — the card prints no attach restriction, so per CR 2.e every unit in play is a legal
#// host regardless of controller or arena. Attaching it to an ENEMY unit hands that unit's controller the
#// granted scry (CR 2.e), which is a bad play but a legal one — the pool has to allow it. Discriminating
#// board: a friendly ground unit, a friendly space unit, an enemy ground unit and an enemy space unit.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0
WithP1SpaceArena: SOR_225:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SEC_213:1:0
WithP1Hand: LAW_125

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# FourSeats_TheOnlyStockedDeckAutoResolvesWithNoPrompt
#// LAW_125 at FOUR seats — same empty-deck gating as LAW_018, asserted the other way round: with ONLY a
#// far seat's deck stocked, the pool narrows to one and the peek must resolve WITHOUT a deck prompt, so
#// the very next answer is the Bottom/Leave choice on P4's card. Under the old code the gating sat behind
#// a `SeatCountForGame() <= 2` short-cut, so above two seats the picker offered all four decks — three of
#// them empty — and "Bottom" was rejected as not a candidate (the mutation message names the whole pool:
#// `[@-&Your_deck&P2_deck&P3_deck&P4_deck]`).
#// Bottoming the top card leaves the deck at 2 with the OTHER card on top.

## GIVEN
CommonSetup: yyw/rrk/{}
SkipPreGame: true
WithTeams: true
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP4Deck: [SOR_046 SOR_128]
WithP1GroundArenaUpgrade: 0:LAW_125

## WHEN
- P1>AttackGroundArena:0:P2G0
- P1>AnswerDecision:Bottom

## EXPECT
SEATCOUNT:4
P4DECKCOUNT:2
P4DECKTOPCARD:SOR_128
