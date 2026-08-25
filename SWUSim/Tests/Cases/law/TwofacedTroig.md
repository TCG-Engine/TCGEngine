# GiveToOpponentCredits
#// LAW_092 Two-Faced Troig (2/4, Sentinel) — When Played: you may have an opponent take control of this
#// unit. If you do, create 2 Credit tokens. Choose YES -> P2 controls it, P1 gets 2 Credits.

## GIVEN
CommonSetup: byk/bgw/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_092

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_092
P1CREDITCOUNT:2

---

# PassKeepControl
#// LAW_092 Two-Faced Troig — When Played "you may have an opponent take control...". Decline (PASS): no
#// Credit tokens are created and P1 keeps control of the unit.

## GIVEN
CommonSetup: byk/bgw/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_092

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_092
P2GROUNDARENACOUNT:0
P1CREDITCOUNT:0

---

# CreditsGoToTheABILITYSControllerNotTheNewController
#// COVERAGE: offer=N/A (the give has no MZ target pool — "an opponent" has exactly one referent in a
#//           two-player game and the only choice is the YES/PASS itself) ·
#//           reqboundary=DefeatAfterTheGive_GoesToItsOWNERSDiscard (a serialize round-trip is inserted
#//           between playing the Troig and answering the give) ·
#//           control=CreditsGoToTheABILITYSControllerNotTheNewController +
#//           DefeatAfterTheGive_GoesToItsOWNERSDiscard · boundary=GiveToOpponentCredits vs
#//           PassKeepControl (give / keep) · decline=PassKeepControl.
#// LAW_092 — "If you do, create 2 Credit tokens." The tokens belong to the player who PLAYED the Troig,
#// NOT to the opponent who now controls it. GiveToOpponentCredits reads only P1's side, so a payout wired
#// to the unit's new controller would pass it unchanged. Here the two seats are made distinguishable: P2
#// starts holding 1 Credit, so after the give P1 must hold exactly 2 (the newly created pair) and P2 must
#// still hold exactly 1 — an unchanged count that a mis-seated payout would have pushed to 3.

## GIVEN
CommonSetup: byk/bgw/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_092
WithP2Credits: 1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:LAW_092
P1CREDITCOUNT:2
P2CREDITCOUNT:1

---

# DefeatAfterTheGive_GoesToItsOWNERSDiscard
#// LAW_092 — giving an opponent CONTROL does not give them OWNERSHIP, and a defeated card always goes to
#// its owner's discard. P1 plays the Troig and hands it to P2, so it now sits in P2's ground arena while
#// P1 still owns it; P1's Industrious Team (4/7) then attacks and kills the 2/4 Troig, and the card must
#// land in P1's discard while P2's stays empty. This is the only state in the file where the Troig's owner
#// and controller differ, so a discard routed by CONTROLLER shows up nowhere else. A serialize round-trip
#// is inserted before the give is answered, so the ownership stamp is proven to survive the request
#// boundary rather than living in a transient of the request that played the card.

## GIVEN
CommonSetup: byk/bgw/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_092
WithP1GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:YES
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:LAW_092
P2DISCARDCOUNT:0
P1CREDITCOUNT:2
P2CREDITCOUNT:0

---

# TwinSuns_CasterChoosesWhoTakesTheTroig
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-24. OFFICIAL RULING (03/27/2026): "If there are multiple
#// opponents, the controlling player chooses which one will be 'an opponent.'"
#// The YES/NO comes FIRST and the seat pick second: that keeps the existing 2-player sequence
#// byte-identical (the picker auto-resolves invisibly at one eligible opponent) and matches the printed
#// order — the "may" is the gate, the seat is the detail.
#// ⚠ NO $eligible filter: any live opponent can take control of a unit. LAW_149 Rey's "opponents can't
#// take control of this unit" is a property of the UNIT and blocks every opponent equally, so it never
#// shrinks the menu (same reasoning as LAW_002).
#// P1 accepts and hands the Troig to SEAT 3. It must leave P1's board for SEAT 3's — not seat 2's, where
#// the old code always sent it — and P1 gets the 2 Credits.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice to get wrong.
#// ⚠ FIXTURE: keep the existing section's byk/bgw aspects — LAW_092 is Cunning/Vigilance, and an
#//   off-aspect deck adds a penalty that pushes cost 3 past a 3-resource pool, so the card is never played
#//   and every assertion fails for an unrelated reason (the first attempt at this section did exactly that).
#// Mutation check: revert to OtherPlayer() and this reds.

## GIVEN
CommonSetup: byk/bgw/{myResources:3}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP1Hand: LAW_092
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P3GROUNDARENACOUNT:1
P3GROUNDARENAUNIT:0:CARDID:LAW_092
P1CREDITCOUNT:2
