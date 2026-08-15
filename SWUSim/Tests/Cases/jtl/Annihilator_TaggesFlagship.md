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

---

# WhenPlayed_HandMatchesOnly_NoDeckPrompt
#// JTL_041 Annihilator — the two searched zones are independent. Here the controller's DECK holds NO
#// name-match, so after the (unconditional) hand discard the deck branch must short-circuit: no deck
#// selection prompt is raised at all and the flow ends. P1 defeats the enemy SOR_179 Boba Fett; P2's
#// hand holds both "Boba Fett" cards (SOR_179 + the pilot JTL_189, matched by TITLE with the subtitle
#// excluded) plus a non-matching Cartel Spacer — the two Bobas are discarded, the Spacer stays. P2's
#// deck is a single Cartel Spacer, so the deck is left untouched and P1 has NO decision pending after
#// answering only the hand-reveal OK. P2 discard = 3 (defeated Boba + 2 hand).
#// ⚠ Flow note: the hand-reveal OK popup is only raised when the opponent's hand is NON-empty — see
#// WhenPlayed_DeckMatchesOnly_EmptyHand below, which must NOT answer an OK.

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
WithP2SpaceArena: SOR_179:1:0
WithP2Hand: [SOR_179 JTL_189 SOR_178]
WithP2Deck: SOR_178

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:OK

## EXPECT
P2SPACEARENACOUNT:0
P2HANDCOUNT:1
P2DECKCOUNT:1
P2DISCARDCOUNT:3
P1NODECISION

---

# WhenPlayed_DeckMatchesOnly_EmptyHand
#// JTL_041 Annihilator — the mirror of the hand-only case: the controller's HAND holds no name-match
#// (it is empty), so the unconditional hand sweep discards nothing, but the DECK search still runs and
#// still offers its per-card pick. P1 defeats the enemy SOR_179 Boba Fett; P2's deck holds both
#// "Boba Fett" cards (SOR_179 + the pilot JTL_189) plus a non-matching Cartel Spacer. P1 selects both
#// matches, leaving a 1-card deck. P2 discard = 3 (defeated Boba + 2 deck) and the hand stays empty —
#// proving neither zone's result is a precondition for the other.
#// ⚠ With an EMPTY opponent hand the hand-reveal OK popup is skipped entirely, so the deck selection is
#// the very next decision — answering a stray OK here would be consumed by the deck search (selecting
#// nothing) and silently no-op the whole deck discard.

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
WithP2SpaceArena: SOR_179:1:0
WithP2Deck: [SOR_179 JTL_189 SOR_178]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:SOR_179,JTL_189

## EXPECT
P2SPACEARENACOUNT:0
P2HANDCOUNT:0
P2DECKCOUNT:1
P2DISCARDCOUNT:3
P1NODECISION

---

# WhenPlayed_NameHuntSkipsUnitsInPlayAndDeployedLeader
#// JTL_041 Annihilator — "search its controller's deck and hand" names exactly two zones. Cards with the
#// matching name that are anywhere ELSE are untouched: other units already in PLAY and a DEPLOYED LEADER
#// of the same name both survive. P2 has the leader JTL_009 Boba Fett (Any Methods Necessary) deployed as
#// a leader unit, plus JTL_189 Boba Fett (Feared Bounty Hunter) as a normal unit, alongside the SOR_179
#// Boba Fett that P1 defeats. The name-hunt discards the SOR_179 copy from hand and the JTL_189 copy from
#// deck, but P2's ground arena still holds BOTH the deployed leader and the in-play JTL_189 unit.
#// P2 discard = 3 (the defeated SOR_179 + 1 hand + 1 deck).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirLeader:JTL_009:1:1:1;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_041
WithP1Resources: 11
WithP2GroundArena: SOR_179:1:0
WithP2GroundArena: JTL_189:1:0
WithP2Hand: [SOR_179 SOR_178]
WithP2Deck: [JTL_189 SOR_178]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:OK
- P1>AnswerDecision:JTL_189

## EXPECT
P2HANDCOUNT:1
P2DECKCOUNT:1
P2DISCARDCOUNT:3
P2LEADER:DEPLOYED
P1NODECISION

---

# WhenDefeated_NameHuntFiresEvenIfDefeatReplaced
#// JTL_041 Annihilator — the WHEN DEFEATED half must behave exactly like the When Played half when the
#// chosen target's defeat is REPLACED rather than prevented (the When Played case is covered by
#// WhenPlayed_NameHuntFiresEvenIfDefeatReplaced above). P2 (active) plays Rival's Fall (SHD_079) to defeat
#// P1's Annihilator → its When Defeated triggers for the non-active controller P1, so it must be drained.
#// P1 targets P2's JTL_049 L3-37, whose own replacement attaches her as a Pilot upgrade to P2's AT-ST
#// (SOR_232) instead of defeating her. A defeat EVENT still fired, so the name-hunt runs: every "L3-37"
#// card (SHD_197 Droid Revolutionary + the JTL_049 copy, matched by TITLE) leaves P2's hand and deck.
#// L3-37 herself is NOT in the discard — she is an upgrade on the AT-ST.
#// P2 discard = 5 (P2's spent Rival's Fall + 2 hand + 2 deck).

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
WithP2GroundArena: JTL_049:1:0
WithP2GroundArena: SOR_232:1:0
WithP2Hand: [SHD_079 SHD_197 JTL_049 SOR_178]
WithP2Deck: [SHD_197 JTL_049 SOR_178]
WithP2Resources: 6

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>Drain
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:OK
- P1>AnswerDecision:SHD_197,JTL_049
- P2>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_049
P2HANDCOUNT:1
P2DECKCOUNT:1
P2DISCARDCOUNT:5
P1NODECISION

---

# DeckHunt_ShufflesOwnersDeckOnly_SearchersDeckUntouched
#// JTL_041 Annihilator — the name-hunt searches and then reshuffles the DECK OWNER's deck. The searching
#// player's OWN deck must not be touched at all: it is never searched, so it must keep both its size and
#// its order. Both players' decks are seeded here; P1 defeats the enemy SOR_179 Boba Fett and discards
#// the matching copy from P2's deck (P2 3 -> 2), while P1's own 3-card deck keeps its exact top card and
#// count. A finalize that resolved the deck under the SEARCHER's frame would shuffle P1's deck instead —
#// invisible to every other section here, because none of them seed a deck for P1.

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
WithP1Deck: [SOR_128 SOR_046 SOR_095]
WithP2SpaceArena: SOR_179:1:0
WithP2Deck: [SOR_179 SOR_178 SOR_046]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:SOR_179

## EXPECT
P2SPACEARENACOUNT:0
P2DECKCOUNT:2
P2DISCARDCOUNT:2
P1DECKCOUNT:3
P1DECKTOPCARD:SOR_128
P1NODECISION

---

# Offer_EnemyUnitsOnlyBothArenas
#// JTL_041 Annihilator — "You may defeat an ENEMY unit." The pool must cover BOTH enemy arenas and must
#// exclude every FRIENDLY unit. P1 has a friendly ground unit (SOR_095) and a friendly space unit
#// (SOR_237) alongside the freshly-played Annihilator; P2 has one unit in each arena. Pool must be
#// exactly the two enemy units.
#// The decision is left PENDING so the offer itself is asserted.

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
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SPACEARENACOUNT:2
P1GROUNDARENACOUNT:1
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0
