# Grit_PowerScalesWithDamage
#// SOR_065 Baze Malbus, Temple Guardian (Ground, 2/5 UQ, Vigilance) — "Grit (+1/+0 for each damage on
#//   him). While you have the initiative, this unit gains Sentinel." Baze with 3 damage attacks the
#//   enemy base for 2+3 = 5.
#// COVERAGE: offer=NoInitiative_EnemyAttacksBaseFreely (with the gate OFF the enemy attack-target pool
#//           still contains P1's base — the explicit BASE answer would throw if out of pool) +
#//           ClaimTurnsSentinelOnMidPhase (gate ON narrows the pool to Baze alone: the attack
#//           auto-resolves onto him, target answer discarded) · reqboundary=OpponentClaims_NextPhase_NoSentinel
#//           (the gate is re-read from serialized state after a full regroup round-trip) ·
#//           control=ControlTaken_GateReadsTheControllersInitiative_Off /_On — the owner-vs-
#//           controller reading IS live and is now measured on a Baze owned by P1 and controlled by
#//           P2: "While YOU have the initiative" resolves against the CONTROLLER, so the gate is
#//           off when the OWNER holds the counter and on when the controller does. (The second
#//           reading, who RESOLVES it, has no fixture: both clauses are static and raise no
#//           decision, so there is nobody to resolve them.) · boundary
#//           pair=StartWithInitiative_SentinelForcesAttack vs
#//           NoInitiative_EnemyAttacksBaseFreely (gate on/off), plus the Grit ladder
#//           Grit_NoDamage_PrintedPower (0 → 2) / Grit_OneDamage_PowerThree (1 → 3) /
#//           Grit_AppliesWhileDefending (2 → 4) / Grit_PowerScalesWithDamage (3 → 5) ·
#//           decline=N/A (both abilities are static — no "you may" anywhere on the card)

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

---

# Grit_NoDamage_PrintedPower
#// SOR_065 Baze Malbus — the zero point of the Grit ladder and the negative that proves the bonus
#//   is damage-driven rather than a flat buff: undamaged, he reads his printed 2/5. Together with
#//   Grit_OneDamage_PowerThree (1 → 3), Grit_AppliesWhileDefending (2 → 4) and
#//   Grit_PowerScalesWithDamage (3 → 5) this pins the scaling at exactly +1 per damage.

## GIVEN
CommonSetup: bbw/ggk
WithP1GroundArena: SOR_065:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# Grit_OneDamage_PowerThree
#// SOR_065 Baze Malbus — the N vs N+1 step on Grit itself: with exactly ONE damage he is 2 + 1 = 3
#//   power, so his base attack deals 3 (not 2, and not 5). Grit_NoDamage_PrintedPower is the N-1
#//   half on the identical board.

## GIVEN
CommonSetup: bbw/ggk
WithP1GroundArena: SOR_065:1:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P1GROUNDARENAUNIT:0:POWER:3

---

# Grit_CombatDamageDoesNotBoostTheSameStrike
#// SOR_065 Baze Malbus — Intended: combat damage is dealt SIMULTANEOUSLY (CR), so the damage the
#//   attacker puts on Baze during THIS attack cannot feed back into the power Baze strikes with in
#//   that same attack. Undamaged Baze is attacked by a 3/3 Battlefield Marine: he deals 2, not 5.
#//   The Marine survives on 2 damage (it would have died to a fed-back 5), Baze ends on 3 — and
#//   only NOW does Grit read those 3, so his power afterwards is 5. P2 holds the initiative, so
#//   Sentinel is off and the explicit unit target is picked from an open pool.

## GIVEN
CommonSetup: bbw/ggk
WithInitiativePlayer: 2
WithActivePlayer: 2
WithP1GroundArena: SOR_065:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:POWER:5

---

# InitiativeSentinel_ForcesAttackOntoGrittedBaze
#// SOR_065 Baze Malbus — the two clauses working together, which no existing section covers: the
#//   initiative gate drags the attack onto Baze AND Grit decides what the attacker walks into.
#//   P1 holds the initiative so Baze has Sentinel and P2's Battlefield Marine cannot reach the
#//   base; Baze already carries 1 damage, so he counters at 3 (not his printed 2) and kills the
#//   3/3 outright. He takes the Marine's 3 back (4 total, alive on 5 HP) and his power then reads
#//   2 + 4 = 6.

## GIVEN
CommonSetup: bbw/ggk
WithInitiativePlayer: 1
WithActivePlayer: 2
WithP1GroundArena: SOR_065:1:1
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:4
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# ControlTaken_GateReadsTheControllersInitiative_Off
#// SOR_065 Baze Malbus — the owner-vs-controller reading of "While YOU have the initiative". Baze
#//   is OWNED by P1 but sits under P2's control (the end state after a take-control effect), and
#//   P1 holds the initiative. "You" is the unit's CONTROLLER, so the gate is OFF: Baze has no
#//   Sentinel and P1's Battlefield Marine walks straight past him into P2's base for 3. Reading the
#//   OWNER's initiative instead would have switched Sentinel on and forced the attack onto Baze —
#//   that is the whole discrimination.

## GIVEN
CommonSetup: bbw/ggk
WithInitiativePlayer: 1
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArenaControlled: SOR_065:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:CARDID:SOR_065
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ControlTaken_GateReadsTheControllersInitiative_On
#// SOR_065 Baze Malbus — the ON half of the same control pair on the identical board with the
#//   initiative moved to P2, the CONTROLLER. Now the gate fires for the seat that holds him: Baze
#//   gains Sentinel, P1's Marine can no longer reach P2's base and its declared base attack is
#//   forced onto Baze (he is the only legal target, so it auto-resolves). Baze takes 3 and counters
#//   for his printed 2.

## GIVEN
CommonSetup: bbw/ggk
WithInitiativePlayer: 2
WithActivePlayer: 1
WithP1GroundArena: SOR_095:1:0
WithP2GroundArenaControlled: SOR_065:1

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:CARDID:SOR_065
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:2
