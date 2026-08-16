# UnrefusableOffer_DefeatedAtRegroup
#// SHD_226 Unrefusable Offer — the stolen unit carries SWU_SNEAK_DEFEAT: after P1 plays SOR_160 under
#// its control via the bounty, the start of the regroup phase defeats it. Since P1 does not own SOR_160,
#// the defeated unit goes to its OWNER's (P2's) discard, leaving P1 with only its original SOR_046.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_160:1:0
WithP2GroundArenaUpgrade: 0:SHD_226

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2DISCARDCOUNT:2

---

# UnrefusableOffer_PlayUnderControlOnBounty
#// SHD_226 Unrefusable Offer — "Attach to a non-leader unit. Attached unit gains: 'Bounty - Play this
#// unit for free (under your control). It enters play ready. At the start of the regroup phase, defeat
#// it.'" P1 attaches it to the enemy SOR_160 (3/2, no innate Bounty), then defeats it with SOR_046: P1
#// collects the bounty and plays SOR_160 into its own arena, ready (P1 now controls it, P2's board empty).

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_160:1:0
WithP2GroundArenaUpgrade: 0:SHD_226

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SOR_160
P1GROUNDARENAUNIT:1:READY

---

# UnrefusableOffer_StolenUnitAttacksTheSameTurn
#// SHD_226 Unrefusable Offer — "It enters play READY" is load-bearing: a unit normally arrives exhausted
#// and cannot act, so the stolen SOR_095 Battlefield Marine being able to attack P2's base for 3 on the
#// very turn it changed hands is the only thing that separates "enters ready" from "enters play". After
#// the swing it is exhausted, which is the same assertion read from the other side.
#// COVERAGE: offer=N/A (the bounty reward takes no target — the unit to play, its controller and its
#//           readiness are all fixed by the card text; the only decision is the collect YES/NO)
#//           decline=UnrefusableOffer_BountyDeclined_UnitStaysInItsOwnersDiscard
#//           control=UnrefusableOffer_PlayUnderControlOnBounty + this section (the unit lands on the
#//           COLLECTOR's side and attacks for the collector) and UnrefusableOffer_DefeatedAtRegroup
#//           (owner is unchanged: the delayed defeat sends it to its OWNER's discard, not the collector's)
#//           boundary=UnrefusableOffer_DefeatedAtRegroup (still in play at regroup → defeated) vs
#//           UnrefusableOffer_StolenUnitAlreadyDefeated_RegroupDoesNothing (already gone → no-op)
#//           reqboundary=UnrefusableOffer_StolenUnitAlreadyDefeated_RegroupDoesNothing (the delayed-defeat
#//           marker is written on one action and read at the start of the regroup phase, several
#//           serialization round-trips later)

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_226

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:1:BASE

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:EXHAUSTED
P2BASEDMG:3

---

# UnrefusableOffer_BountyDeclined_UnitStaysInItsOwnersDiscard
#// SHD_226 Unrefusable Offer — "should do nothing if the bounty is not collected". Declining the collect
#// leaves the defeated SOR_095 Battlefield Marine exactly where a normal defeat put it: in its OWNER's
#// discard, alongside the Unrefusable Offer that was attached to it. P1's board is untouched.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_226

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2DISCARDCOUNT:2
P1DISCARDCOUNT:0
P1NODECISION

---

# UnrefusableOffer_OnATokenUnitThereIsNothingToPlay
#// SHD_226 Unrefusable Offer — a defeated TOKEN unit ceases to exist rather than going to a discard pile,
#// so "play this unit for free" has nothing to play. P1 collects the bounty off P2's Battle Droid token
#// and gets nothing: P1's board is still just its attacker, and the only card in P2's discard is the
#// Unrefusable Offer itself (the token leaves no card behind).

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2GroundArenaUpgrade: 0:SHD_226

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P2DISCARDCOUNT:1
P2DISCARDUNIT:0:CARDID:SHD_226
P1NODECISION

---

# UnrefusableOffer_StolenUnitAlreadyDefeated_RegroupDoesNothing
#// SHD_226 Unrefusable Offer — "At the start of the regroup phase, defeat it" is a DELAYED effect, and it
#// must find nothing to do when the unit is already gone. P1 steals SOR_095 Battlefield Marine, throws it
#// at P2's SOR_232 AT-ST (6/7) where it dies immediately, and then crosses into regroup: the AT-ST and
#// P1's own attacker are both untouched. The boundary partner of UnrefusableOffer_DefeatedAtRegroup,
#// where the stolen unit is still on the board when regroup starts.
#// Both decks are seeded because reaching regroup with an empty deck adds 3 damage per empty draw.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_232:1:0]
WithP2GroundArenaUpgrade: 0:SHD_226
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SOR_095 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AttackGroundArena:1:0
- P1>Pass

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232
P2DISCARDCOUNT:2

---

# UnrefusableOffer_StolenUnitDefeatedThenResourced_RegroupLeavesItAlone
#// SHD_226 Unrefusable Offer — "At the start of the regroup phase, defeat it" must not chase the card
#// into another zone. P1 steals P2's SOR_095 Battlefield Marine, throws it into P2's SOR_232 AT-ST where
#// it dies, and P2 then plays SHD_105 Spark of Hope on it ("Choose a unit in your discard pile. If it was
#// defeated this phase, put it into play as a resource") — the marine comes back as one of P2's
#// RESOURCES. Crossing into the regroup phase must leave it there: P2 keeps 4 resources rather than 3,
#// and the only cards in its discard are the Unrefusable Offer and the spent Spark of Hope — the marine
#// is not among them.
#// P2 acts here, so P1OnlyActions is deliberately absent and both players pass into regroup; both decks
#// are seeded so the regroup draws do not deal empty-deck damage.

## GIVEN
CommonSetup: yyk/ggw/{theirResources:3;theirhandCardIds:SHD_105}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: [SOR_095:1:0 SOR_232:1:0]
WithP2GroundArenaUpgrade: 0:SHD_226
WithP1Deck: [SOR_095 SOR_046]
WithP2Deck: [SOR_095 SOR_046]

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P2>Pass
- P1>AttackGroundArena:1:0
- P2>PlayHand:0
- P1>Pass
- P2>Pass

## EXPECT
P2RESCOUNT:4
P2DISCARDCOUNT:2
P2DISCARDUNIT:0:CARDID:SHD_226
P2DISCARDUNIT:1:CARDID:SHD_105
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_232

---

# UnrefusableOffer_CollectedWhenTheUnitIsCAPTURED
#// SHD_226 Unrefusable Offer — a Bounty is collected "when this unit is defeated OR CAPTURED", so the
#// steal does not need a defeat. P1 plays SHD_131 Take Captive, its SOR_046 captures P2's bountied
#// SOR_095 Battlefield Marine, and the bounty fires off the CAPTURE: the marine comes out from under the
#// captor and into P1's arena, ready, under P1's control. Load-bearing negative: the captor ends with no
#// subcards, proving the marine was taken OUT of captivity rather than copied out of a discard pile.

## GIVEN
CommonSetup: ggk/ggk/{myResources:5;myhandCardIds:SHD_131}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:SHD_226

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SOR_095
P1GROUNDARENAUNIT:1:READY
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0

---

# UnrefusableOffer_UniquenessRuleDefeatsOneCopy
#// SHD_226 Unrefusable Offer — playing the stolen unit under P1's control can hand P1 a SECOND copy of a
#// unique unit, and CR 8.19.1.b makes its controller defeat copies until one remains. Both players field
#// SOR_142 Sabine Wren; P1's Sabine attacks P2's (2 combat damage plus the 1 from her own On Attack
#// finishes the 2/3), collects the bounty, and the stolen copy arrives on P1's side — at which point P1
#// must defeat one of the two. P1 defeats the newly arrived copy, so its OWNER (P2) takes it into their
#// discard and P1 keeps the Sabine it started with.

## GIVEN
CommonSetup: yyk/yyk
P1OnlyActions: true
WithP1GroundArena: SOR_142:1:0
WithP2GroundArena: SOR_142:1:0
WithP2GroundArenaUpgrade: 0:SHD_226

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:2
P2DISCARDCOUNT:2
