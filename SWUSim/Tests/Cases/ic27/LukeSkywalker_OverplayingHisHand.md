# PutsAnEnemyUnitOnTheBottom_ItsOwnerDrawsTwo
#// IC27_165 Luke Skywalker — Overplaying His Hand. Unit, cost 8, [Cunning][Heroism], 9/9, Force/Rebel, unique.
#// "When Played: You may put an enemy non-leader unit on the bottom of its owner's deck. Its owner draws 2 cards."
#// The unit goes to the bottom FIRST, then its OWNER draws 2 — so the drawn cards are the owner's two top
#// cards and the unit is left on the bottom. You draw nothing. Declining leaves nothing for "its" to refer
#// to, so nobody draws. "Its owner" is DETERMINED by the chosen unit (no opponent prompt).
#// Fixture: CommonSetup yyw = Cunning base + Cunning/Heroism leader, so Luke costs his printed 8.
#//
#// COVERAGE: offer=Offer_EnemyNonLeaderUnits_BothArenas_NotYoursNotLeaders (incl. a stolen unit you control)
#//           decline=Decline_WithOneLegalTarget_NothingMovesNobodyDraws
#//                 + NoEnemyNonLeaderUnit_NoPrompt_NobodyDraws (no target at all)
#//           boundary=OwnerDeckOfOne_PutFirstThenDraw_DrawsTheUnitBack (put-then-draw order) +
#//                    OwnerDeckEmpty_DrawsTheUnitThenTakesThreeForTheSecond (0 vs 1 card in deck)
#//           control=EnemyControlsYourUnit_ItGoesToYourDeck_YouDrawTwo (owner ≠ controller)
#//           reqboundary=RequestBoundary_BeforeTheChoice
#//           modes=2P,TwinSuns ("its owner" — determined, a far seat: TwinSuns_FarSeatOwnerDraws_NotSeatTwo),
#//                 TeamSuns ("enemy": TeamSuns_TeammatesUnitIsNotEnemy)

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_165
WithP1Deck: [SOR_046 SOR_237]
WithP2Deck: [SOR_128 SOR_225]
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:IC27_165
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095
P2HANDCOUNT:2
P2HANDCARD:0:SOR_128
P2HANDCARD:1:SOR_225
P2DECKCOUNT:1
P2DECKTOPCARD:SEC_080
P2DISCARDCOUNT:0
P1HANDCOUNT:0
P1DECKCOUNT:2
P2BASEDMG:0
P1NODECISION
NOEXTRAACTION

---

# Offer_EnemyNonLeaderUnits_BothArenas_NotYoursNotLeaders
#// The pool, left pending: the enemy's ground AND space units only. Excluded: your own Marine, the
#// opponent's Dark Trooper that YOU control (an enemy-owned unit is friendly while you control it —
#// controlled units sort after your own, so it is ground index 1, Luke index 2), and the enemy's deployed
#// leader (deployed leaders sort after plain units, so it is their ground index 1).

## GIVEN
CommonSetup: yyw/rrk/{myResources:8;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: IC27_165
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaControlled: SEC_080:2
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2Deck: [SOR_046 SOR_237]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SEC_080
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P1SELECTABLEEXACT:theirGroundArena-0&theirSpaceArena-0

---

# PutsAnEnemySpaceUnit
#// Either arena: the enemy's space unit goes to the bottom of its owner's deck.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_165
WithP2Deck: [SOR_046 SOR_237]
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENACOUNT:1
P2DECKCOUNT:1
P2DECKTOPCARD:SOR_225
P2HANDCOUNT:2

---

# Decline_WithOneLegalTarget_NothingMovesNobodyDraws
#// "You may": a lone enemy unit still prompts (a mandatory choose would have resolved by itself), and
#// declining moves nothing and draws nothing — there is no "its owner" without a chosen unit.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_165
WithP1Deck: [SOR_046 SOR_237]
WithP2Deck: [SOR_128 SOR_225]
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2HANDCOUNT:0
P2DECKCOUNT:2
P1HANDCOUNT:0
P1DECKCOUNT:2
P1GROUNDARENACOUNT:1
P1NODECISION

---

# NoEnemyNonLeaderUnit_NoPrompt_NobodyDraws
#// Only your own unit and the enemy's deployed leader: nothing to choose, no prompt, no draws.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8;theirLeaderDeployed:true}
P1OnlyActions: true
WithP1Hand: IC27_165
WithP1GroundArena: SOR_095:1:0
WithP1Deck: [SOR_046 SOR_237]
WithP2Deck: [SOR_128 SOR_225]

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:ISLEADERUNIT
P2HANDCOUNT:0
P2DECKCOUNT:2
P1HANDCOUNT:0
P1NODECISION

---

# OwnerDeckOfOne_PutFirstThenDraw_DrawsTheUnitBack
#// Order: put on the bottom, THEN draw. With one card in the owner's deck, the second draw is the very
#// unit Luke just put there — no deck-out. Drawing first would have dealt 3 to their base and left the
#// unit in the deck.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_165
WithP2Deck: SOR_128
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:2
P2HANDCARD:0:SOR_128
P2HANDCARD:1:SEC_080
P2DECKCOUNT:0
P2BASEDMG:0

---

# OwnerDeckEmpty_DrawsTheUnitThenTakesThreeForTheSecond
#// An empty deck: the unit becomes the deck, the first draw takes it back, and the second can't be drawn —
#// 3 damage to its owner's base (CR 6.1).

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_165
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1
P2HANDCARD:0:SEC_080
P2DECKCOUNT:0
P2BASEDMG:3
P1BASEDMG:0

---

# TokenUnit_CeasesToExist_ItsOwnerStillDraws
#// A token unit is a legal enemy non-leader unit. It never enters a deck (it ceases to exist), but the
#// draw is a separate sentence, not "If you do" — its owner still draws 2.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_165
WithP2Deck: [SOR_128 SOR_225]
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2DECKCOUNT:0
P2HANDCOUNT:2
P2DISCARDCOUNT:0

---

# UpgradedUnit_UpgradeIsDefeated_ShieldCeases_OnlyTheUnitGoesToTheDeck
#// The unit leaves play without being defeated; its upgrades are defeated (CR 9.3): Academy Training goes
#// to its owner's discard, the Shield token ceases. Only the unit itself reaches the deck.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_165
WithP2Deck: [SOR_128 SOR_225]
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaUpgrade: 0:SOR_120
WithP2GroundArenaUpgrade: 0:SOR_T02
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SOR_120
P2DECKCOUNT:1
P2DECKTOPCARD:SEC_080
P2HANDCOUNT:2

---

# EnemyControlsYourUnit_ItGoesToYourDeck_YouDrawTwo
#// Owner ≠ controller: the opponent controls YOUR Marine, so it is an enemy unit — but its OWNER is you.
#// It goes to the bottom of YOUR deck and YOU draw 2; the controller draws nothing.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_165
WithP1Deck: [SOR_046 SOR_237]
WithP2Deck: [SOR_128 SOR_225]
WithP2GroundArena: SEC_080:1:0
WithP2GroundArenaControlled: SOR_095:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P1HANDCOUNT:2
P1HANDCARD:0:SOR_046
P1HANDCARD:1:SOR_237
P1DECKCOUNT:1
P1DECKTOPCARD:SOR_095
P2HANDCOUNT:0
P2DECKCOUNT:2

---

# RequestBoundary_BeforeTheChoice
#// The happy path with a fresh request before the answer. Nothing is held in memory: the owner is read
#// off the chosen unit when the answer arrives.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
P1OnlyActions: true
WithP1Hand: IC27_165
WithP2Deck: [SOR_128 SOR_225]
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2DECKCOUNT:1
P2DECKTOPCARD:SEC_080
P2HANDCOUNT:2
P1NODECISION

---

# TwinSuns_FarSeatOwnerDraws_NotSeatTwo
#// Three seats, no teams. Both opponents' units are offered; put seat 3's unit on the bottom of SEAT 3's
#// deck, and seat 3 draws 2. Seat 2 (the two-seat "other player") draws nothing.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1Hand: IC27_165
WithP2Deck: [SOR_046 SOR_237]
WithP3Deck: [SOR_128 SOR_225]
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p3GroundArena-0

## EXPECT
SEATCOUNT:3
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:SOR_046
P3DECKCOUNT:1
P3DECKTOPCARD:SOR_095
P3HANDCOUNT:2
P2HANDCOUNT:0
P2DECKCOUNT:2
P2GROUNDARENACOUNT:1

---

# TwinSuns_OfferSpansBothOpponents
## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 123
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP1Hand: IC27_165
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:3
P1SELECTABLEEXACT:p2GroundArena-0&p3GroundArena-0

---

# TeamSuns_TeammatesUnitIsNotEnemy
#// "Enemy" is the other team: both opponents' units are offered, your teammate's (seat 3) is not.

## GIVEN
CommonSetup: yyw/rrk/{myResources:8}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_024
WithP4Base: SOR_024
WithP1Hand: IC27_165
WithP2GroundArena: SEC_080:1:0
WithP3GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1SELECTABLEEXACT:p2GroundArena-0&p4GroundArena-0
