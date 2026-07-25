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
