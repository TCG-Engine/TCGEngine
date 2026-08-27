# OpponentDeclinesBounce
#// LOF_222 A Precarious Predicament — Return an enemy non-leader unit unless its controller says "It could
#// be worse." P1 targets SOR_046; P2 declines (does not object), so SOR_046 is returned to P2's hand.

## GIVEN
CommonSetup: yyk/ggw/{myResources:2;handCardIds:LOF_222}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:0
P2HANDCOUNT:1

---

# SelectableEnemyNonLeaderUnits
#// LOF_222 — the choice offers every enemy non-leader unit. P2 controls Wampa (SOR_164) and a Battlefield
#// Marine (SOR_095) that P1 owns but P2 controls; both are selectable — exactly the Wampa and the
#// Battlefield Marine.

## GIVEN
CommonSetup: yyk/ggw/{myResources:2;handCardIds:LOF_222}
P1OnlyActions: true
WithP2GroundArena: SOR_164:1:0
WithP2ControlledUnit: SOR_095:1

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1

---

# ReturnToOwnerNotController
#// LOF_222 — the unit returns to its OWNER's hand, not the controller's. The Battlefield Marine (SOR_095) is
#// owned by P1 but controlled by P2. P1 targets it; P2 declines to say "It could be worse", so the Marine
#// returns to P1's hand (owner), leaving P2 with only Wampa.

## GIVEN
CommonSetup: yyk/ggw/{myResources:2;handCardIds:LOF_222}
WithActivePlayer: 1
WithP2GroundArena: SOR_164:1:0
WithP2ControlledUnit: SOR_095:1

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P2>AnswerDecision:NO

## EXPECT
P1HANDCOUNT:1
P2GROUNDARENACOUNT:1

---

# OpponentSaysItCouldBeWorse_PlayItsWorseFromHand
#// LOF_222 — if the controller says "It could be worse" (keeps the unit), P1 may play a card named It's Worse
#// (LOF_264, "Defeat a non-leader unit") from hand for FREE. P2 says yes (keep Wampa); P1 elects to play
#// It's Worse from hand, which auto-targets the lone Wampa and defeats it. It's Worse goes to discard along
#// with the Predicament.

## GIVEN
CommonSetup: yyk/ggw/{myResources:2;handCardIds:LOF_222,LOF_264}
WithActivePlayer: 1
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1DISCARDCOUNT:2

---

# OpponentSaysItCouldBeWorse_DeclineToPlayItsWorse
#// LOF_222 — playing It's Worse is a "you may". P2 says "It could be worse" (keeps Wampa); P1 declines to
#// play It's Worse from hand. Nothing is defeated: Wampa stays, It's Worse stays in P1's hand, and only the
#// spent Predicament is in discard.

## GIVEN
CommonSetup: yyk/ggw/{myResources:2;handCardIds:LOF_222,LOF_264}
WithActivePlayer: 1
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:YES
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:1
P1HANDCOUNT:1
P1DISCARDCOUNT:1

---

# OpponentSaysItCouldBeWorse_PlayItsWorseFromRESOURCES
#// LOF_222 — the printed text is "play a card named It's Worse from your hand OR RESOURCES for free."
#// Here P1 holds NO copy in hand; its only It's Worse (LOF_264) sits in the RESOURCE zone. P2 keeps the
#// Wampa, so P1 must still be offered the free play, and taking it defeats the Wampa.
#// ⚠ RED: LOF_222#1 scans the caster's HAND only and returns early when it finds nothing there
#// (`// none in hand (the "or resources" path is deferred)`), so the offer is never raised.
#// DISCRIMINATES: the resource count drops 3 -> 2 as the card leaves the zone, and P1's hand is empty
#// throughout, so a hand-only implementation cannot reach this end state by any route.

## GIVEN
CommonSetup: yyk/ggw/{handCardIds:LOF_222}
WithActivePlayer: 1
WithP1Resources: 2:SOR_095:1,1:LOF_264:1
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:YES
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1HANDCOUNT:0
P1RESCOUNT:2

---

# TwinSuns_TheYESNOGoesToTheUNITSController
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — §1b "its controller" family.
#// "Return an enemy non-leader unit UNLESS ITS CONTROLLER says 'It could be worse'." The cross-player
#// YESNO must go to the seat that actually controls the chosen unit — a DETERMINED seat the handler
#// already holds a reference to. It used OtherPlayer($player), i.e. literally seat 2, so above two seats
#// the prompt was sent to a bystander who was asked whether to keep a unit that is not theirs — while the
#// real controller was never asked at all.
#//
#// P1 targets a unit on SEAT 4 (P1's opponents are 2 and 4; 3 is a teammate). P2 also has a unit, so the
#// legacy answer (seat 2) is a live wrong seat rather than an empty one — sweep rule 6. P4 answers NO and
#// its unit bounces; P2's unit is untouched. Under the old code P4 would have had no decision to answer.

## GIVEN
CommonSetup: yyk/ggw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 2
WithP1Hand: LOF_222
WithP2GroundArena: SOR_046:1:0
WithP4GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:p4GroundArena-0
- P4>AnswerDecision:NO

## EXPECT
SEATCOUNT:4
P4GROUNDARENACOUNT:0
P4HANDCOUNT:1
P2GROUNDARENACOUNT:1
