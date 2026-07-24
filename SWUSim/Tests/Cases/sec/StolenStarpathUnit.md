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
