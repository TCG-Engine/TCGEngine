# Deployed_OnAttack_MillHeal
#// SOR_047 Kanan Jarrus — the REAL deployed-leader path (not an arena fixture): deploy Kanan via the
#// Epic Action, then attack. His deploy-side OnAttack fires: 1 friendly Spectre (Kanan himself) →
#// mill 1 from the defender's deck (Aggression) → 1 distinct aspect → heal 1 from P1's base (2 → 1).
#// Kanan's 4 power hits P2's base. (Explicit leader — CommonSetup's 'bw' code maps to Luke, not Kanan.)

## GIVEN
P1LeaderBase: SOR_047/SOR_021:2
P2LeaderBase: SOR_014/SOR_021
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 6
WithP2Deck: SOR_172

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:DEPLOYED
P1BASEDMG:1
P2BASEDMG:4
P2DECKCOUNT:0
P2DISCARDCOUNT:1

---

# OnAttack_MillPerSpectre_HealPerAspect
#// SOR_047 Kanan Jarrus — "On Attack: You may discard 1 card from the defending player's deck for
#// each friendly SPECTRE unit. Heal 1 damage from your base for each different aspect among the
#// discarded cards." 2 friendly Spectre (Kanan + Chopper) → mill 2 from P2's deck (Aggression +
#// Aggression/Villainy = 2 DISTINCT aspects) → heal 2 from P1's base (3 → 1). Kanan's 4 combat damage
#// still hits P2's base.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_047:1:0
WithP1GroundArena: SOR_188:1:0
WithP2Deck: SOR_172
WithP2Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1BASEDMG:1
P2BASEDMG:4
P2DECKCOUNT:0
P2DISCARDCOUNT:2

---

# OnAttack_SameAspect_HealOne
#// SOR_047 Kanan Jarrus — heal is per DISTINCT aspect, not per card. 2 friendly Spectre (Kanan +
#// Chopper) mill 2 cards that share the SAME single aspect (Aggression + Aggression) → only 1
#// distinct aspect → heal 1 (NOT 2). Guards the distinct-vs-count logic.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_047:1:0
WithP1GroundArena: SOR_188:1:0
WithP2Deck: SOR_172
WithP2Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1BASEDMG:2
P2BASEDMG:4
P2DECKCOUNT:0
P2DISCARDCOUNT:2

---

# TwinSuns_MillsTheACTUALDefendingPlayersDeck
#// "On Attack: You may discard 1 card from THE DEFENDING PLAYER's deck for each friendly SPECTRE unit.
#// Heal 1 damage from your base for each different aspect among the discarded cards."
#//
#// This one used GetOpponent(), the worst of the three legacy helpers: `1->2, 2->1, else NULL`. For a
#// seat-1 attacker it named seat 2 (the wrong player); for a seat-3 or seat-4 attacker it returned NULL,
#// so SWUMillTopCard(null) milled nothing at all and the heal silently never happened.
#//
#// Kanan is himself a Spectre and is the only one here, so exactly ONE card is milled. Seat 4 (the
#// defender) has a one-card deck and seat 2 has two, so the correct read empties seat 4 and leaves
#// seat 2 untouched -- the legacy read does exactly the reverse. The heal is asserted as well: Open Fire
#// is a single aspect, so seat 1's base goes 2 -> 1.
#//
#// FIXTURE NOTE: arena fixture rather than the deploy path (covered by the first section in this file).
#// Deploying and then attacking is TWO actions, and P1OnlyActions only returns the turn past seat 2 --
#// at four seats the turn moves on to seats 3/4 and the attack never happens.

## GIVEN
CommonSetup: bgw/bbw/{myBaseDamage:2; theirBase:SOR_021}
SkipPreGame: true
P1OnlyActions: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1GroundArena: SOR_047:1:0
WithP2Deck: [SOR_172 SOR_172]
WithP4Deck: SOR_172

## WHEN
- P1>AttackGroundArena:0:P4B
- P1>AnswerDecision:YES

## EXPECT
SEATCOUNT:4
P1BASEDMG:1
P4BASEDMG:4
P4DECKCOUNT:0
P4DISCARDCOUNT:1
P2DECKCOUNT:2
