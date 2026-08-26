# OnAttack_NameCard_SpyPerCopy
#// SEC_210 Stolen Starpath Unit (Upgrade) — Attached unit gains "On Attack: Name a card. The defending
#//   player reveals their hand. For each card with that name, create a Spy token." Host SOR_095 bears
#//   SEC_210, attacks the base; name "Battlefield Marine"; P2 hand has 2 → create 2 Spy tokens.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_210
WithP2Hand: SOR_095
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:Battlefield Marine

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:3
P1NODECISION

---

# OnAttack_NameCard_NoMatch_NoSpy
#// SEC_210 Stolen Starpath Unit — naming a card absent from the opponent's hand creates 0 Spy tokens.
#//   Host SOR_095 bears SEC_210 and attacks; P2 hand holds 2 Battlefield Marines but we name Wampa → no
#//   matches → no Spy tokens (only the host remains in the ground arena).

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_210
WithP2Hand: SOR_095
WithP2Hand: SOR_095

## WHEN
- P1>AttackGroundArena:0
- P1>AnswerDecision:Wampa

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1NODECISION

---

# OnAttack_OpponentHandEmpty_NoPrompt
#// SEC_210 Stolen Starpath Unit — with the defending player's hand empty there is nothing to reveal and
#//   no copies to count, so the naming is skipped entirely (no NAMECARD prompt) and 0 Spy tokens are
#//   created. Host SOR_095 attacks; only the host remains.

## GIVEN
CommonSetup: yyk/rrk
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_210

## WHEN
- P1>AttackGroundArena:0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
P1NODECISION

---

# TwinSuns_ReadsTheACTUALDefendingPlayersHand
#// "On Attack: Name a card. THE DEFENDING PLAYER reveals their hand. For each card in their hand with
#// that name, create a Spy token." Both the empty-hand early-out and the counting loop used
#// OtherPlayer($player), so the card read seat 2's hand no matter who was attacked.
#//
#// Seat 4 (the defender) holds TWO Battlefield Marines, seat 2 holds THREE. Correct = 2 Spy tokens, so
#// seat 1's ground arena is host + 2 = 3. The legacy read gives 4. Seat 3 also holds copies, so a
#// fan-out-style mistake would give yet another number.

## GIVEN
CommonSetup: yyk/rrk/{theirBase:SOR_021}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArenaUpgrade: 0:SEC_210
WithP2Hand: [SOR_095 SOR_095 SOR_095]
WithP3Hand: [SOR_095 SOR_095 SOR_095]
WithP4Hand: [SOR_095 SOR_095]

## WHEN
- P1>AttackGroundArena:0:P4B
- P1>AnswerDecision:Battlefield Marine

## EXPECT
SEATCOUNT:4
P1GROUNDARENACOUNT:3
