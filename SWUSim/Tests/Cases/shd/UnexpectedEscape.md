# ExhaustAndRescue
#// SHD_076 Unexpected Escape (1-cost event, Vigilance) — "Exhaust a unit. You may rescue a captured card
#// guarded by that unit." First the Discerning Veteran (SHD_120) captures SOR_046; then Unexpected Escape
#// exhausts the Veteran and rescues SOR_046 back to P2's arena.
#// COVERAGE: offer=ExhaustOffer_AnyUnitOnEitherSideCapturedOrNot (the "Exhaust a unit" pool, PENDING +
#//   P1SELECTABLEEXACT — friendly and enemy, captors and non-captors alike) and
#//   RescueOffer_OnlyTheChosenUnitsOwnCaptives (the rescue pool is narrowed to THAT unit's captives) ·
#//   decline=SingleCaptive_DeclineIsStillOffered + MultipleCaptives_DeclineLeavesEveryCaptiveGuarded
#//   ("you MAY rescue" — a lone captive must still prompt, and both pool sizes can be declined) ·
#//   boundary=NoCaptives_TheExhaustStillHappensAndNothingIsPrompted vs the rescue sections is the
#//   unconditional-exhaust pair (the exhaust is a separate sentence, not a cost for the rescue), and
#//   SingleCaptive_RescueIsStillOfferedAndCanBeTaken vs SingleCaptive_DeclineIsStillOffered is the
#//   take/decline pair on the same board · reqboundary=MultipleCaptives_RescueOneFromAnEnemyCaptor
#//   LeavesTheRest (the captor is chosen at one decision and its captive list is rebuilt and staged for
#//   the next, so the captor UID and subcard index have to survive the hop) · control=N/A — the event
#//   changes no unit's control; the nearest axis is WHOSE unit is exhausted and whose captives move, and
#//   both directions are covered (enemy captor freeing a P1-owned card in MultipleCaptives_RescueOneFrom
#//   AnEnemyCaptorLeavesTheRest, friendly captor freeing a P2-owned card in SingleCaptive_RescueIsStill
#//   OfferedAndCanBeTaken).

## GIVEN
CommonSetup: bgk/bgk/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_120
WithP1Hand: SHD_076
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_120
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046

---

# ExhaustOffer_AnyUnitOnEitherSideCapturedOrNot
#// THE OFFER AXIS for "Exhaust a unit." There is no friendly/enemy qualifier and no "that is guarding a
#// captured card" qualifier, so the pool is every unit in play in every arena — P1's own SOR_225 (which
#// happens to be guarding a captive), P2's SHD_120 (guarding two) and P2's SHD_029 (guarding none).
#// Restricting the pool to enemy units, or to captors, would both be visible here. Three legal targets
#// keep the pick interactive, so the offer is read while the decision is still PENDING.
#// Shared fixture for the sections below: P1's TIE guards one card owned by P2; P2's Veteran guards two
#// owned by P1; P2's Pyke Sentinel guards nothing.

## GIVEN
CommonSetup: bgk/bgk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_076
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaCaptive: 0:SOR_241
WithP2GroundArena: [SHD_120:1:0 SHD_029:1:0]
WithP2GroundArenaCaptive: 0:SOR_164
WithP2GroundArenaCaptive: 0:SOR_232

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:mySpaceArena-0&theirGroundArena-0&theirGroundArena-1

---

# RescueOffer_OnlyTheChosenUnitsOwnCaptives
#// The second offer: "a captured card guarded by THAT unit" — not any captured card on the board. With
#// P2's Veteran chosen, the rescue pool is exactly its two captives; the card guarded by P1's own TIE is
#// out of it even though it is also a captured card and P1 is the one resolving. The captives are staged
#// into the temp zone in guard order, so the pool is myTempZone-0 and myTempZone-1 and nothing else.
#// The rescue decision is left PENDING here; the sections below resolve and decline it.

## GIVEN
CommonSetup: bgk/bgk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_076
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaCaptive: 0:SOR_241
WithP2GroundArena: [SHD_120:1:0 SHD_029:1:0]
WithP2GroundArenaCaptive: 0:SOR_164
WithP2GroundArenaCaptive: 0:SOR_232

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myTempZone-0&myTempZone-1

---

# MultipleCaptives_RescueOneFromAnEnemyCaptorLeavesTheRest
#// The full resolution of a multi-captive rescue against an ENEMY captor. P1 exhausts P2's SHD_120 and
#// rescues the first card it guards; the rescued unit goes back to its OWNER's arena EXHAUSTED
#// (CR 8.34.4), which here is P1's ground arena. Exactly one card is freed: SHD_120 keeps its second
#// captive, and the unrelated captive on P1's own TIE is untouched — a rescue that swept "all captives
#// guarded by that unit", or that reached across to other captors, would show up in both of those.
#// P2's SHD_029 is not the chosen unit and must stay READY.

## GIVEN
CommonSetup: bgk/bgk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_076
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaCaptive: 0:SOR_241
WithP2GroundArena: [SHD_120:1:0 SHD_029:1:0]
WithP2GroundArenaCaptive: 0:SOR_164
WithP2GroundArenaCaptive: 0:SOR_232

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_120
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_232
P2GROUNDARENAUNIT:1:CARDID:SHD_029
P2GROUNDARENAUNIT:1:READY
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_164
P1GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_241
P1NODECISION

---

# MultipleCaptives_DeclineLeavesEveryCaptiveGuarded
#// THE DECLINE BRANCH with a real choice on the table. "You MAY rescue" — declining with the
#// choose-nothing token must still leave the exhaust done and every captive exactly where it was: both of
#// SHD_120's captives still guarded, P1's own captive still guarded, and no unit returned to any arena.
#// The pair with the section above is what proves the rescue is optional rather than automatic.

## GIVEN
CommonSetup: bgk/bgk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_076
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaCaptive: 0:SOR_241
WithP2GroundArena: [SHD_120:1:0 SHD_029:1:0]
WithP2GroundArenaCaptive: 0:SOR_164
WithP2GroundArenaCaptive: 0:SOR_232

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SHD_120
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P2GROUNDARENAUNIT:1:READY
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1NODECISION

---

# SingleCaptive_RescueIsStillOfferedAndCanBeTaken
#// A captor guarding exactly ONE card. A lone legal target normally auto-resolves, but "you MAY rescue"
#// has to keep prompting so the player can decline — and taking the offer here frees the card to its
#// owner's arena EXHAUSTED. P1 chooses its OWN TIE fighter, so this is also the friendly-captor half of
#// the "Exhaust a unit" pool: the card it guarded is owned by P2 and returns to P2's SPACE arena, the
#// arena of the returning unit rather than the captor's.
#// P2's SHD_120 is not the chosen unit, so both of its captives stay guarded.

## GIVEN
CommonSetup: bgk/bgk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_076
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaCaptive: 0:SOR_241
WithP2GroundArena: [SHD_120:1:0 SHD_029:1:0]
WithP2GroundArenaCaptive: 0:SOR_164
WithP2GroundArenaCaptive: 0:SOR_232

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:myTempZone-0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_241
P2SPACEARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# SingleCaptive_DeclineIsStillOffered
#// THE SHARP PAIR with the section above, and the reason the single-captive case cannot be allowed to
#// auto-resolve: with one card guarded there is still a real decision, because declining and accepting
#// produce different boards. Declined, the exhaust still lands but the captive stays guarded and P2's
#// space arena stays empty. If the engine ever auto-rescued the only captive, this section is what
#// catches it.

## GIVEN
CommonSetup: bgk/bgk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_076
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaCaptive: 0:SOR_241
WithP2GroundArena: [SHD_120:1:0 SHD_029:1:0]
WithP2GroundArenaCaptive: 0:SOR_164
WithP2GroundArenaCaptive: 0:SOR_232

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:SOR_241
P2SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1NODECISION

---

# NoCaptives_TheExhaustStillHappensAndNothingIsPrompted
#// The exhaust is UNCONDITIONAL — it is a separate sentence from the rescue, not a cost paid for it. A
#// unit guarding nothing is a perfectly legal choice: SHD_029 is exhausted, the rescue clause finds no
#// captive to offer and closes with NO prompt at all (not a prompt with an empty pool), and every captive
#// elsewhere on the board is left exactly as it was.

## GIVEN
CommonSetup: bgk/bgk/{myResources:2}
P1OnlyActions: true
WithP1Hand: SHD_076
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaCaptive: 0:SOR_241
WithP2GroundArena: [SHD_120:1:0 SHD_029:1:0]
WithP2GroundArenaCaptive: 0:SOR_164
WithP2GroundArenaCaptive: 0:SOR_232

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:CARDID:SHD_029
P2GROUNDARENAUNIT:1:EXHAUSTED
P2GROUNDARENAUNIT:0:CARDID:SHD_120
P2GROUNDARENAUNIT:0:READY
P2GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:READY
P1NODECISION
