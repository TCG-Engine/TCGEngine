# WhenPlayed_DeclineNoPeek
#// JTL_041 Annihilator — the defeat is optional ("You may defeat an enemy unit"). When P1 DECLINES
#// (AnswerDecision:-), no enemy unit is defeated, so there is NO name-hunt and — critically — NO peek:
#// P2's hand and deck are never shown (no OK popups are queued). P2 keeps its unit, hand copy, and deck
#// copy; nothing is discarded and no decision is left pending. Proves the peek is gated on a defeat.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_041
WithP1Resources: 11
WithP2SpaceArena: SOR_225:1:0
WithP2Deck: SOR_225
WithP2Hand: SOR_225

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_225
P2DECKCOUNT:1
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P1NODECISION

---

# WhenPlayed_DefeatAndNameHunt
#// JTL_041 Annihilator — When Played: You may defeat an enemy unit, then search its controller's deck
#// AND hand for every card with that unit's name and discard them. P1 plays JTL_041 and defeats the
#// enemy SOR_225 in play; because a unit WAS defeated, P1 is shown P2's hand then P2's deck as two
#// information-only OK popups (the searched zones). Only the SOR_225 copies are name-matched: the lone
#// deck copy and lone hand copy of SOR_225 are discarded (P2 discard = 3: the defeated unit + deck copy
#// + hand copy). The non-matching filler cards are untouched, so a 32-card deck drops to 31 and a
#// 6-card hand drops to 5. Filler obeys the SWU CR max of 3 copies per card.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_041
WithP1Resources: 11
WithP2SpaceArena: SOR_225:1:0
#// P2 deck: 32 cards = 1 SOR_225 (name-matched, discarded) + 31 non-matching fillers (max 3 copies each)
WithP2Deck: [SOR_225 SEC_080 SEC_080 SEC_080 SOR_128 SOR_128 SOR_128 SOR_095 SOR_095 SOR_095 SOR_046 SOR_046 SOR_046 SOR_237 SOR_237 SOR_237 SOR_063 SOR_063 SOR_063 SOR_207 SOR_207 SOR_207 JTL_069 JTL_069 JTL_069 LAW_124 LAW_124 LAW_124 LAW_180 LAW_180 LAW_180 SOR_044]
#// P2 hand: 6 cards = 1 SOR_225 (name-matched, discarded) + 5 non-matching fillers
WithP2Hand: [SOR_225 SEC_080 SEC_080 SEC_080 SOR_128 SOR_128]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:OK
- P1>AnswerDecision:OK

## EXPECT
P2SPACEARENACOUNT:0
P2DECKCOUNT:31
P2HANDCOUNT:5
P2DISCARDCOUNT:3
P1NODECISION
LOGCONTAINS:searched

---

# WhenPlayed_NoEnemyUnit_NoPeek
#// JTL_041 Annihilator — with NO enemy unit in play there is nothing to defeat, so the ability fizzles
#// before offering anything: no "may defeat" prompt, no name-hunt, and NO peek at P2's hand or deck.
#// P2 keeps its hand copy and deck copy of SOR_225, nothing is discarded, and no decision is pending.
#// Proves the peek never happens when no enemy unit is defeated (even though P2 holds matching cards).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_041
WithP1Resources: 11
WithP2Deck: SOR_225
WithP2Hand: SOR_225

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_041
P2DECKCOUNT:1
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P1NODECISION
