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
- P1>AnswerDecision:SOR_225

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

---

# WhenPlayed_TargetCannotBeDefeated_NoNameHunt
#// JTL_041 Annihilator — "You may defeat an enemy unit. If you do, search…". The name-hunt (and the
#// peek at the controller's zones) are gated on the defeat ACTUALLY happening. P1 plays JTL_041 and
#// targets P2's SHD_187 Lurking TIE Phantom, which "can't be captured, damaged, or defeated by enemy
#// card abilities". The defeat is blocked, so SHD_187 stays in play and — because "you" didn't defeat
#// it — there is NO search: P2's hand copy and deck copy of SHD_187 are NOT discarded, discard stays 0,
#// and no peek popup is queued (no decision left pending). Regression guard: the handler previously
#// name-hunted unconditionally after SWUDefeatUnit, discarding the copies even when the defeat failed.

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
WithP2SpaceArena: SHD_187:1:0
WithP2Deck: SHD_187
WithP2Hand: SHD_187

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SHD_187
P2DECKCOUNT:1
P2HANDCOUNT:1
P2DISCARDCOUNT:0
P1NODECISION

---

# WhenDefeated_DefeatAndNameHunt
#// JTL_041 Annihilator — the ability is symmetric: "When Played/When Defeated: You may defeat an enemy
#// unit. If you do, search its controller's deck and hand for each card with that unit's name and discard
#// them." This proves the WHEN DEFEATED half of the symmetric ability.
#// P1's Annihilator sits in space. P2 (active) plays Rival's Fall (SHD_079) to defeat it → Annihilator's
#// When Defeated triggers for its controller P1 (non-active, so drained). P1 defeats the enemy SOR_179
#// Boba Fett in play, then the two peek popups (P2's hand, then deck) fire and every "Boba Fett"-named
#// card is discarded: name matches by TITLE (subtitle excluded), so SOR_179 AND the pilot JTL_189 both
#// go — 2 from hand + 2 from deck. P2 discard = 6 (P2's own spent Rival's Fall + defeated Boba + 2 hand
#// + 2 deck); the Wampa (SOR_164), the non-matching Cartel Turncoat in hand, and Cartel Spacer in deck
#// are untouched.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1SpaceArena: JTL_041:1:0
WithP2GroundArena: SOR_179:1:0
WithP2GroundArena: SOR_164:1:0
WithP2Hand: [SHD_079 SOR_179 JTL_189 SHD_195]
WithP2Deck: [SOR_179 JTL_189 SOR_178]
WithP2Resources: 6

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:OK
- P1>AnswerDecision:SOR_179,JTL_189

## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_164
P2HANDCOUNT:1
P2DECKCOUNT:1
P2DISCARDCOUNT:6
P1NODECISION

---

# WhenPlayed_NameHuntFiresEvenIfDefeatReplaced
#// JTL_041 Annihilator — "You may defeat an enemy unit. If you do, search…". The search is gated on the
#// DEFEAT EVENT firing, not on the target ending up in the discard, so it still runs when the defeat is
#// REPLACED by another effect (unlike an outright PREVENTED defeat — see WhenPlayed_TargetCannotBeDefeated).
#// P1 plays Annihilator and targets P2's JTL_049 L3-37, whose own replacement ("if this would be defeated,
#// instead attach her as a Pilot upgrade to a friendly pilot-less Vehicle") redirects the defeat: L3-37
#// leaves the arena and attaches to P2's AT-ST (SOR_232). The name-hunt STILL fires: every "L3-37"-named
#// card (SHD_197 and the JTL_049 copies) is discarded from P2's hand (unconditional) and deck (selected).
#// L3-37 herself is NOT in the discard — she became an upgrade. P2 discard = 4 (2 hand + 2 deck).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: JTL_041
WithP1Resources: 11
WithP2GroundArena: JTL_049:1:0
WithP2GroundArena: SOR_232:1:0
WithP2Hand: [SHD_197 JTL_049 SOR_178]
WithP2Deck: [SHD_197 JTL_049 SOR_178]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:OK
- P1>AnswerDecision:SHD_197,JTL_049
- P2>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_049
P2HANDCOUNT:1
P2DECKCOUNT:1
P2DISCARDCOUNT:4
P1NODECISION

---

# WhenPlayed_DeckDiscardIsPerCardOptional
#// JTL_041 Annihilator — the two searched zones differ: the HAND discard is unconditional, but the DECK
#// discard is an interactive per-card pick (the searcher chooses WHICH name-matches to discard and may keep
#// the rest). P1 defeats the enemy SOR_179 Boba Fett. P2's hand holds two "Boba Fett" cards (SOR_179 + the
#// pilot JTL_189) — both auto-discarded — plus a non-matching Cartel Turncoat. P2's deck holds the same two
#// Boba matches plus a Cartel Spacer; at the deck prompt P1 selects ONLY SOR_179, so the pilot JTL_189
#// stays in the deck. Hand ends at 1 (Cartel Turncoat); deck ends at 2 (kept JTL_189 + Cartel Spacer);
#// P2 discard = 4 (defeated Boba + 2 hand + the 1 chosen deck copy).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_041
WithP1Resources: 11
WithP2SpaceArena: SOR_179:1:0
WithP2Hand: [SOR_179 JTL_189 SHD_195]
WithP2Deck: [SOR_179 JTL_189 SOR_178]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:OK
- P1>AnswerDecision:SOR_179

## EXPECT
P2SPACEARENACOUNT:0
P2HANDCOUNT:1
P2DECKCOUNT:2
P2DISCARDCOUNT:4
P1NODECISION
