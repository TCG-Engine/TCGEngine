# NoMap_DeepCardUnreachable
#// ASH_084 control — WITHOUT Arcana Star Map, Tarkin's search stays at top 5, which is all non-Imperial
#// SOR_063 filler, so the depth-7 Imperial (SOR_085) is never seen and nothing is drawn (hand stays empty).
#// COVERAGE: offer=N/A (the doubling changes a COUNT, not a target pool; the search's own pick is a
#//                 CardID answer, and every section pins reach by which card is drawable) ·
#//           decline=NoMap_DeepCardUnreachable + WhenDefeatedSearch_NOTDoubled… (the empty answer) ·
#//           boundary=this section vs SearchDoubled (5 vs 10 across a depth-7 card) +
#//                 NotDoubled_AnENTIREDECKSearch (the clamp at deck size) ·
#//           control=Doubled_FollowsTheHostsCONTROLLER_NotItsOwner (owner ≠ controller) +
#//                 Doubled_ForTheHOSTsController_WhenTheMapIsOnAnENEMYUnit +
#//                 NotDoubled_TheOPPONENTsSearch (the scope negative) ·
#//           reqboundary=N/A (the doubling is recomputed from live board state inside the search funnel;
#//                 nothing is written before a decision and read behind it)
## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Resources: 4
WithP1Hand: SOR_084
WithP1GroundArena: SOR_095:1:0
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_085
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:
## EXPECT
P1HANDCOUNT:0

---

# SearchDoubled
#// ASH_084 Arcana Star Map (Upgrade, cost 1) — "Attached unit gains: if you would search a number of cards
#// from your deck, search twice that number instead." P1 controls SOR_095 wearing ASH_084, then plays
#// SOR_084 Grand Moff Tarkin (search top 5 for Imperial). Doubled to top 10, the search reaches the lone
#// Imperial (SOR_085) at depth 7 and draws it (it would be unreachable in the top 5).
## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Resources: 4
WithP1Hand: SOR_084
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_084
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_085
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085
## EXPECT
P1HANDCOUNT:1

---

# NotDoubled_TheOPPONENTsSearch
#// ASH_084 — the scope negative. The doubling is gated on the SEARCHING player controlling a unit wearing
#// the Star Map, so P1's Star Map must do nothing for P2's search. P2 plays Tarkin (top 5 for an Imperial)
#// against the same stacked deck whose only Imperial sits at depth 7: at 5 it is unreachable, at a
#// doubled 10 it would be found. P2 draws nothing.
#// Without this, a doubling implemented as "is a Star Map anywhere in play" passes every existing section.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_084
WithP2Resources: 4
WithP2Hand: SOR_084
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_085
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithActivePlayer: 2

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:

## EXPECT
P2HANDCOUNT:0

---

# Doubled_ForTheHOSTsController_WhenTheMapIsOnAnENEMYUnit
#// ASH_084 — "ATTACHED UNIT gains …", so the ability belongs to whoever controls the HOST, not to whoever
#// played the upgrade. An upgrade with no printed controller restriction may be attached to any unit
#// (CR 2.e), so P1 can hang the Star Map on P2's unit — and it then doubles P2's searches, against P1.
#// Same stacked deck: P2 reaches the depth-7 Imperial only because the search doubled to 10.
#// This is the mirror of the section above and the pair is what pins the ability to the HOST's side.

## GIVEN
CommonSetup: ggk/ggk
SkipPreGame: true
WithP2GroundArena: SOR_095:1:0
WithP2GroundArenaUpgrade: 0:ASH_084
WithP2Resources: 4
WithP2Hand: SOR_084
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_085
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithP2Deck: SOR_063
WithActivePlayer: 2

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:SOR_085

## EXPECT
P2HANDCOUNT:1

---

# WhenDefeatedSearch_IsDoubledWhileTheMapIsStillOnANOTHERUnit
#// ASH_084 — the CONTROL half of the ordering pair below. LOF_057 Owen Lars (0/3) attacks a 4/7 and dies
#// to the counter-attack; his "When Defeated: search the top 5 for a Force unit" then resolves while the
#// Star Map is still in play on a DIFFERENT, surviving unit — so the search doubles to 10 and reaches
#// SOR_045 Yoda at depth 7, who is unreachable at 5.
#// ⚠ This control is what makes the section below meaningful: a "not doubled" negative passes trivially
#// if the search never ran at all, so the depth-7 card must be provably reachable on an identical board.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_057:1:0
WithP1GroundArena: SOR_063:1:0
WithP1GroundArenaUpgrade: 1:ASH_084
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_045
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:SOR_045

## EXPECT
P1HANDCOUNT:1

---

# WhenDefeatedSearch_NOTDoubled_TheMapLeftPlayWithItsHost
#// ASH_084 — the ordering case. The Star Map is on OWEN HIMSELF. He is defeated, the map leaves play with
#// him, and only THEN does his When Defeated search resolve — so the map is gone and the search stays at
#// 5. Yoda sits at depth 7 and is never seen: P1 draws nothing.
#// The trigger was QUEUED while the map was still attached, which is what makes this worth pinning: an
#// implementation that snapshotted the doubling at trigger time would draw Yoda here, and the control
#// above proves Yoda is reachable when the map genuinely survives.
#// ⚠ The Star Map is +0/+3, so Owen is 0/6 wearing it and is seeded at 2 damage; the 4-power counter
#// then finishes him. Without the seed he survives and the section tests nothing.

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_057:1:2
WithP1GroundArenaUpgrade: 0:ASH_084
WithP2GroundArena: LAW_124:1:0
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_045
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P1>AnswerDecision:

## EXPECT
P1GROUNDARENACOUNT:0
P1HANDCOUNT:0

---

# Doubled_FollowsTheHostsCONTROLLER_NotItsOwner
#// ASH_084 — "attached unit gains" binds the ability to whoever CONTROLS the host at search time, so a
#// unit that has changed hands carries the Star Map's doubling to its NEW controller.
#// P2 OWNS the host; P1 CONTROLS it. P1's Tarkin search must double to 10 and reach the depth-7 Imperial.
#// The doubling gate walks the searching player's controlled units, so an owner-scoped read fails here
#// and passes every other section in this file.

## GIVEN
CommonSetup: ggk/bbk
SkipPreGame: true
WithP1Resources: 4
WithP1Hand: SOR_084
WithP1GroundArenaControlled: SOR_095:2
WithP1GroundArenaUpgrade: 0:ASH_084
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_085
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085

## EXPECT
P1HANDCOUNT:1

---

# NotDoubled_AnENTIREDECKSearch_IsUnchangedAndSafe
#// ASH_084 — "search twice that number" cannot exceed the deck. SOR_042 Search Your Feelings searches the
#// WHOLE deck, so the doubling clamps straight back to the deck size and the effect is unchanged: every
#// card is still reachable, including the very last one, and nothing over-splices.
#// The bottom card (SOR_085 at depth 10 of 10) is drawn, which is only possible if the search covered the
#// entire deck and the doubled count was clamped rather than used to splice past the end.

## GIVEN
CommonSetup: bbk/bbk
SkipPreGame: true
WithP1Resources: 4
WithP1Hand: SOR_042
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:ASH_084
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_063
WithP1Deck: SOR_085

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:SOR_085

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:9
