# Grit_PowerScalesWithDamage
#// SOR_065 Baze Malbus, Temple Guardian (Ground, 2/5 UQ, Vigilance) — "Grit (+1/+0 for each damage on
#//   him). While you have the initiative, this unit gains Sentinel." Baze with 3 damage attacks the
#//   enemy base for 2+3 = 5.
#// COVERAGE: offer=NoInitiative_EnemyAttacksBaseFreely (with the gate OFF the enemy attack-target pool
#//           still contains P1's base — the explicit BASE answer would throw if out of pool) +
#//           ClaimTurnsSentinelOnMidPhase (gate ON narrows the pool to Baze alone: the attack
#//           auto-resolves onto him, target answer discarded) · reqboundary=OpponentClaims_NextPhase_NoSentinel
#//           (the gate is re-read from serialized state after a full regroup round-trip) ·
#//           control=N/A (the gate reads the CONTROLLER's initiative and Sentinel protects the
#//           controller's side by definition; there is no per-unit marker to strand on a control
#//           change) · boundary pair=StartWithInitiative_SentinelForcesAttack vs
#//           NoInitiative_EnemyAttacksBaseFreely (gate on/off) · decline=N/A (both abilities are
#//           static — no "you may" anywhere on the card)

## GIVEN
CommonSetup: bbw/ggk
WithP1GroundArena: SOR_065:1:3

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:5
P1BASEDMG:0

---

# Grit_AppliesWhileDefending
#// Grit is not attack-gated: a Baze with 2 damage has 4 power even as the DEFENDER. The enemy Rebel
#//   Pathfinder (2/3) attacks into him and dies to the 4-power counter (without Grit, 2 power would
#//   have left it alive at 2 damage). Baze takes 2 more (4 total, survives on 5 HP). P2 holds the
#//   initiative so Sentinel is off and the explicit unit answer picks Baze from the open pool.

## GIVEN
CommonSetup: bbw/ggk
WithInitiativePlayer: 2
WithActivePlayer: 2
WithP1GroundArena: SOR_065:1:2
WithP2GroundArena: SOR_239:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# NoInitiative_KeywordOff
#// While the OPPONENT holds the initiative counter, Baze does not have Sentinel.

## GIVEN
CommonSetup: bbw/ggk
WithInitiativePlayer: 2
WithActivePlayer: 1
WithP1GroundArena: SOR_065:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# NoInitiative_EnemyAttacksBaseFreely
#// Gate OFF: P2 has the initiative, so P2's Wampa may attack P1's base straight past Baze — the base
#//   is a legal member of the attack-target pool (the explicit BASE answer would throw otherwise).

## GIVEN
CommonSetup: bbw/ggk
WithInitiativePlayer: 2
WithActivePlayer: 2
WithP1GroundArena: SOR_065:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# ClaimTurnsSentinelOnMidPhase
#// The gate flips ON the moment P1 claims: P2's first attack (pre-claim) hits the base, P1 claims the
#//   initiative, and P2's second attack is forced onto Baze — he is now the only legal target, so the
#//   attack auto-resolves onto him regardless of the declared BASE target. Baze (2 power, no damage
#//   yet) counters the 3/3 Marine for 2.

## GIVEN
CommonSetup: bbw/ggk
WithInitiativePlayer: 2
WithActivePlayer: 2
WithP1GroundArena: SOR_065:1:0
WithP2GroundArena: [SOR_164:1:0 SOR_095:1:0]

## WHEN
- P2>AttackGroundArena:0:BASE
- P1>Claim
- P2>AttackGroundArena:1:BASE

## EXPECT
P1BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:1:DAMAGE:2

---

# StartWithInitiative_SentinelForcesAttack
#// Gate ON from the start of the phase: P1 holds the (unclaimed) counter, so Baze has Sentinel and
#//   P2's Wampa cannot reach the base — the attack auto-resolves onto Baze (pool of one). Baze takes
#//   4 (survives on 5 HP), the Wampa takes 2 back.

## GIVEN
CommonSetup: bbw/ggk
WithInitiativePlayer: 1
WithActivePlayer: 2
WithP1GroundArena: SOR_065:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# OpponentClaims_NextPhase_NoSentinel
#// The gate does not stick across rounds: P1 starts with the initiative (Sentinel on), P2 claims it,
#//   and after the regroup the NEW phase opens with P2 holding the initiative — Sentinel is off and
#//   P2's Wampa hits the base for 4. Decks are seeded so the regroup draw doesn't hit an empty deck.

## GIVEN
CommonSetup: bbw/ggk
WithInitiativePlayer: 1
WithActivePlayer: 1
WithP1GroundArena: SOR_065:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>Pass
- P2>Claim
- P1>ResourcePass
- P2>ResourcePass
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
