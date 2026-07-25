# OnDefense_MillNonUnit_NoOption
#// SEC_090 Director Krennic — when the milled card is NOT a unit (SOR_251 Confiscate, an Event), there is
#//   no return option: the card just stays milled in the discard and combat proceeds with no decision.
#//   Proves the "if it's a unit" gate and that a non-unit mill doesn't hang combat.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_090:1:0
WithP2Deck: SOR_251
WithP2Deck: SOR_046
WithP2Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2DECKCOUNT:2
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
P2NODECISION

---

# OnDefense_MillUnit_DeclineReturn
#// SEC_090 Director Krennic — the return is "you may". P2 declines (AnswerDecision:NO), so the milled
#//   unit (SOR_095) stays in P2's discard. P2 deck 3→2, discard 0→1, hand unchanged. Proves the optional
#//   return decline path no-ops cleanly.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_090:1:0
WithP2Deck: SOR_095
WithP2Deck: SOR_046
WithP2Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:NO

## EXPECT
P2DECKCOUNT:2
P2HANDCOUNT:1
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# OnDefense_MillUnit_ReturnToHand
#// SEC_090 Director Krennic (Ground, 8/10, Command/Villainy) — Sentinel + On Defense (when this unit is
#//   attacked): discard a card from your deck; if it's a unit, you may return it to your hand.
#// P2 controls Krennic (defender). P1's SOR_046 (3/7) attacks it; before damage Krennic mills the top of
#// P2's deck (SOR_095, a unit), and P2 chooses to return it to hand. Net: P2 deck 3→2, the milled unit is
#// back in hand (P2 hand 1→2), discard stays 0. Combat then resolves: Krennic takes 3 (survives), counters
#// 8 → SOR_046 (7 HP) is defeated.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_090:1:0
WithP2Deck: SOR_095
WithP2Deck: SOR_046
WithP2Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:YES

## EXPECT
P2DECKCOUNT:2
P2HANDCOUNT:2
P2DISCARDCOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:0

---

# OnDefense_MillUpgrade_NoOption
#// SEC_090 Director Krennic — when the milled card is an UPGRADE (SOR_057 Protector), there is no return
#//   option (return is "if it's a unit"). The upgrade stays in the discard and combat proceeds with no
#//   decision. Deck 3->2, discard 0->1.

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_090:1:0
WithP2Deck: SOR_057
WithP2Deck: SOR_046
WithP2Deck: SOR_046

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2DECKCOUNT:2
P2DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
P2NODECISION

---

# OnDefense_EmptyDeck_NoBreak
#// SEC_090 Director Krennic — On Defense with an EMPTY deck must not break the game: there is nothing to
#//   mill, no decision appears, and combat resolves normally (Krennic takes 3, counters, defeats SOR_046).

## GIVEN
CommonSetup: ggw/grk/{theirHandCardIds:SOR_225}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_090:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:0
P1NODECISION
P2NODECISION
