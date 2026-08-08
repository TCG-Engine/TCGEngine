# DefeatNight_ReplayFree
#// LOF_036 Old Daka — When Played: may defeat a friendly Night unit (not Old Daka). Then may play that
#// unit from the discard pile for free. P1 plays Daka, defeats the friendly Nightsister Warrior (LOF_059),
#// then replays it for free from the discard.

## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_036}
P1OnlyActions: true
WithP1GroundArena: LOF_059:1:0
WithP1Deck: SOR_095

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:LOF_059
P1DISCARDCOUNT:0

---

# DeclineReplay_StaysInDiscard
#// LOF_036 Old Daka — the replay is optional. Defeat the Nightsister Warrior (LOF_059) but decline the
#// free replay → it stays in the discard; only Daka is on the board.
## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_036}
P1OnlyActions: true
WithP1GroundArena: LOF_059:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:NO
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_036
P1DISCARDCOUNT:1

---

# NoNightUnit_DakaPlaysAlone
#// LOF_036 — with no friendly Night unit, the defeat stage is skipped entirely; Daka just enters play.
## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_036}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:2
P1DISCARDCOUNT:0

---

# DeclineDefeat_DoesNothing
#// LOF_036 Old Daka — the When-Played defeat is a "may". P1 plays Daka with a friendly Night unit (LOF_059
#// Nightsister Warrior) present but DECLINES the defeat → the Night unit survives, nothing is discarded, and
#// only Daka joins it on the board. Intended: "should allow passing at the defeat stage".

## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_036}
P1OnlyActions: true
WithP1GroundArena: LOF_059:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:LOF_059
P1DISCARDCOUNT:0

---

# DefeatNightLeader_ReturnsToBase_NoReplay
#// LOF_036 Old Daka — defeating a friendly Night LEADER (deployed Asajj Ventress JTL_001, Force/Night)
#// returns her to the base as a leader (leaders are not "defeated" into the discard), so there is nothing to
#// replay from the discard pile. Only Daka remains in the arena and the leader is undeployed. Intended: "should
#// allow defeating a friendly Night leader and then do nothing".

## GIVEN
CommonSetup: bbk/ggw/{myLeader:JTL_001:1:1:1;myResources:5;handCardIds:LOF_036}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_036
P1DISCARDCOUNT:0

---

# DefeatStolenNightUnit_GoesToOwnersDiscard_NoReplay
#// LOF_036 Old Daka — "friendly" is about CONTROL, so a Night unit P1 has taken control of IS a legal
#// defeat target. But the replay clause says "from YOUR discard pile", and a defeated unit goes to its
#// OWNER's discard — so the stolen Nightsister Warrior (LOF_059, owned by P2) lands in P2's discard and
#// P1 gets nothing back. Daka alone remains on P1's board. (The engine still shows the "play it for
#// free?" prompt here; answering YES is a harmless no-op, asserted below — the card is simply not in
#// P1's discard to take.)
## GIVEN
CommonSetup: bbk/ggw/{myResources:5;handCardIds:LOF_036}
P1OnlyActions: true
WithP1GroundArenaControlled: LOF_059:2
WithP1Deck: SOR_095
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:YES
## EXPECT
P1GROUNDARENACOUNT:1
P1DISCARDCOUNT:0
P2DISCARDCOUNT:1
