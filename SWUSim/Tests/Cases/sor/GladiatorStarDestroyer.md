# Sentinel_ExpiresNextPhase
#// SOR_086 Gladiator Star Destroyer — "Give a unit Sentinel for this phase." The grant is now a
#// CardID turn-effect token ("SOR_086") resolved by the turn-effect registry to Sentinel/phase, and
#// expired by the centralized duration-driven SWUExpireTurnEffects at RegroupPhaseStart. After both
#// players pass (ending the action phase → regroup), the Battlefield Marine no longer has Sentinel.
#// COVERAGE: offer=WhenPlayed_Offer_AnyUnitEitherSideBothArenas (decision left pending; the pool is
#//           every unit in play, both sides and both arenas, including the Destroyer itself), with
#//           NoOtherUnits_AutoTargetsItself as the pool-of-one auto-resolve partner (P1NODECISION) ·
#//           decline=N/A (no "you may": the grant is a mandatory single choose, and the pool can never
#//           be empty because the Destroyer is itself a legal target — see NoOtherUnits_AutoTargetsItself) ·
#//           control=GrantedToAStolenUnit_GuardsItsControllersBase (owner ≠ controller: the granted
#//           Sentinel is read from the recipient's CONTROLLER, guarding P1's base against its owner) ·
#//           boundary pair=WhenPlayed_GrantsSentinel (grant present this phase) vs
#//           Sentinel_ExpiresNextPhase (the same grant gone after the regroup), plus the arena-scope
#//           pair GrantedSentinel_ForcesTheEnemyAttackOntoIt (ground recipient binds a ground attacker)
#//           vs ArenaScope_SpaceSentinelDoesNotStopAGroundAttack (space recipient does not) ·
#//           reqboundary=SimulateRequestBoundary_SentinelGrantAcrossBoundary (the phase-scoped turn
#//           effect is written by a fresh process from the serialized answer alone)

## GIVEN
CommonSetup: grk/grk/{myResources:8}
WithP1Hand: SOR_086
WithP1GroundArena: SEC_080:1:0    # Battlefield Marine — idx 0, the Sentinel recipient

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>Pass
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# WhenPlayed_GrantsSentinel
#// SOR_086 Gladiator Star Destroyer (5/6, Space) — When Played: Give a unit Sentinel for this
#// phase. P1 plays it and chooses the friendly Battlefield Marine, which then has the Sentinel
#// keyword (a phase-scoped TurnEffect grant). Uses the new HASKEYWORD/NOTKEYWORD assertions.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_086
WithP1GroundArena: SEC_080:1:0    # Battlefield Marine — idx 0, the Sentinel recipient

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel

---

# SimulateRequestBoundary_SentinelGrantAcrossBoundary
#// SOR_086 Gladiator Star Destroyer — the When Played "give a unit Sentinel" choose ends the request in
#// production, so the phase-scoped TurnEffect grant is written by a fresh process from the answer alone.
#// Mirrors WhenPlayed_GrantsSentinel with the boundary inserted before the answer.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_086
WithP1GroundArena: SEC_080:1:0    # Battlefield Marine — idx 0, the Sentinel recipient

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:NOTKEYWORD:Sentinel

---

# WhenPlayed_Offer_AnyUnitEitherSideBothArenas
#// SOR_086 Gladiator Star Destroyer — the OFFER axis. "Give A UNIT Sentinel" names no controller and no
#// arena, so per the unqualified-target reading the pool is EVERY unit in play, friendly and enemy,
#// ground and space — including the Destroyer itself, which is already in play when its own When Played
#// resolves. Answering would only prove one branch, so the decision is left PENDING and the pool read
#// directly: four candidates across all four arena/side combinations.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_086
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SHD_060:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# NoOtherUnits_AutoTargetsItself
#// SOR_086 Gladiator Star Destroyer — the no-other-target case. With an empty board the pool narrows to
#// the Destroyer itself (it is in play by the time its own When Played resolves), so the mandatory
#// single-target choose AUTO-RESOLVES: no decision is left pending and the Destroyer holds the keyword.
#// This is also the proof that the card can never fizzle for want of a target — a self-exclusion
#// wrongly copied from an "another unit" template would leave an empty pool here.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_086

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# GrantedSentinel_ForcesTheEnemyAttackOntoIt
#// SOR_086 Gladiator Star Destroyer — the granted keyword must actually WORK, not merely read back.
#// P1 plays the Destroyer and hands Sentinel to its ground Battlefield Marine; P2's Consular Security
#// Force then declares an attack on P1's base and is redirected onto the Marine (pool of one). The base
#// takes nothing, the Marine takes 3 and dies to it, and the attacker takes the Marine's 3 back.
#// Existing sections assert only HASKEYWORD; this is the behavioural half.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
WithActivePlayer: 1
WithP1Hand: SOR_086
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3

---

# GrantedToAnEnemyUnit_ThenBlocksYourOwnAttack
#// SOR_086 Gladiator Star Destroyer — "a unit" is unqualified, so the ENEMY side is a legal (if
#// self-defeating) choice, and choosing it must have the real consequence rather than being silently
#// dropped. P1 gives Sentinel to P2's Consular Security Force; P1's own Battlefield Marine then
#// declares an attack on P2's base and is redirected onto that very unit. The base takes nothing, the
#// enemy unit takes 3, and P1's 3/3 attacker takes the 3 back and dies. A "friendly units only" filter would make this
#// section unanswerable, and a grant that only tracked friendly units would let the base attack through.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
P1OnlyActions: true
WithP1Hand: SOR_086
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENACOUNT:0

---

# ArenaScope_SpaceSentinelDoesNotStopAGroundAttack
#// SOR_086 Gladiator Star Destroyer — "Units in THIS ARENA can't attack…". P1 gives Sentinel to the
#// Destroyer itself, which sits in SPACE, so the grant constrains space attackers only: P2's GROUND
#// Consular Security Force still reaches P1's base for its full 3. Pairs with
#// GrantedSentinel_ForcesTheEnemyAttackOntoIt, where the same grant on a GROUND unit does bind the same
#// ground attacker — the arena of the recipient is the whole difference.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
WithActivePlayer: 1
WithP1Hand: SOR_086
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:3
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1SPACEARENAUNIT:0:DAMAGE:0

---

# GrantedToAStolenUnit_GuardsItsControllersBase
#// SOR_086 Gladiator Star Destroyer — the CONTROL axis. The Battlefield Marine is OWNED by P2 but
#// CONTROLLED by P1 (the end state after a take-control effect). P1 plays the Destroyer and grants that
#// stolen unit Sentinel: "your non-Sentinel units or your base" is read from the CONTROLLER, so it must
#// now guard P1's base against its own owner. P2's attack is redirected onto it. A grant that stamped
#// the recipient's OWNER seat would leave P1's base exposed here.

## GIVEN
CommonSetup: grk/grk/{myResources:8}
WithActivePlayer: 1
WithP1Hand: SOR_086
WithP1GroundArenaControlled: SOR_095:2
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:3
