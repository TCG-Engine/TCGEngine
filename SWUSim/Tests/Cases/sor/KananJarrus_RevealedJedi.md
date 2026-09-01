# Deployed_OnAttack_MillHeal
#// SOR_047 Kanan Jarrus — the REAL deployed-leader path (not an arena fixture): deploy Kanan via the
#// Epic Action, then attack. His deploy-side OnAttack fires: 1 friendly Spectre (Kanan himself) →
#// mill 1 from the defender's deck (Aggression) → 1 distinct aspect → heal 1 from P1's base (2 → 1).
#// Kanan's 4 power hits P2's base. (Explicit leader — CommonSetup's 'bw' code maps to Luke, not Kanan.)
#// COVERAGE: offer=N/A (nothing is chosen — the mill count is derived from the friendly Spectre
#//           count, the milled cards are the defending deck's top cards, and the defending player is
#//           fixed by the attack declaration; the only decision is the YES/NO for the whole clause,
#//           and TwinSuns_MillsTheACTUALDefendingPlayersDeck pins the "which deck" half by making
#//           three candidate decks distinguishable) · decline=Decline_NoMillNoHeal (NO → no mill, no
#//           heal, combat unchanged) · control=Control_StolenSpectreCountsForTheCONTROLLER (a
#//           P2-owned Chopper in P1's arena counts toward "each friendly SPECTRE unit", and the heal
#//           lands on the controller's base) · boundary pair=OnAttack_MillPerSpectre_HealPerAspect
#//           (2 milled cards, 2 distinct aspects → heal 2) vs OnAttack_SameAspect_HealOne (2 milled
#//           cards, 1 distinct aspect → heal 1) — the heal is per DISTINCT aspect, not per card ·
#//           reqboundary=Deployed_OnAttack_MillHeal (the leader unit is written by the deploy request
#//           and the On Attack reads it back in a separately-serialized attack request)

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

---

# Decline_NoMillNoHeal
#// Intended: "YOU MAY discard 1 card from the defending player's deck for each friendly SPECTRE unit."
#// The whole clause is optional. Same board as OnAttack_MillPerSpectre_HealPerAspect (Kanan + Chopper
#// = 2 friendly Spectre, a 2-card enemy deck of two distinct aspects, P1's base on 3 damage), but P1
#// answers NO: nothing is milled and nothing is healed. The combat itself is unaffected — Kanan's 4
#// power still hits P2's base — so this is distinguishable from an attack that never happened.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_047:1:0
WithP1GroundArena: SOR_188:1:0
WithP2Deck: SOR_172
WithP2Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:NO

## EXPECT
P1BASEDMG:3
P2BASEDMG:4
P2DECKCOUNT:2
P2DISCARDCOUNT:0
P1NODECISION

---

# Control_StolenSpectreCountsForTheCONTROLLER
#// Intended: "for each FRIENDLY Spectre unit" counts by CONTROL, not by ownership (CR: a unit is
#// friendly to the player who controls it), and "heal 1 damage from YOUR base" heals the player
#// resolving the ability. Chopper (SOR_188, Spectre) sits in P1's arena but is OWNED by P2 — the end
#// state after a take-control effect. He is friendly to P1, so P1 has TWO Spectre units and mills 2:
#// Open Fire (Aggression) + Death Star Stormtrooper (Aggression/Villainy) = 2 distinct aspects → P1's
#// base heals 2 (3 → 1).
#// An ownership-framed count would see only Kanan, mill 1, and heal 1 (base ends on 2 with a card
#// still in P2's deck) — so deck 0 / discard 2 / base 1 pins the control reading three ways over.
#// P2's base takes only Kanan's 4 combat damage: the heal never lands on the stolen unit's owner.
#// ⚠ A Controlled unit seats AFTER every plain WithP1GroundArena line, so Kanan is index 0 (the
#// attacker) and the stolen Chopper is index 1.

## GIVEN
CommonSetup: bbw/rrk/{myBaseDamage:3}
P1OnlyActions: true
WithP1GroundArena: SOR_047:1:0
WithP1GroundArenaControlled: SOR_188:2    # Chopper — P1 controls, P2 owns
WithP2Deck: SOR_172
WithP2Deck: SOR_128

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SOR_047
P1GROUNDARENAUNIT:1:CARDID:SOR_188
P1BASEDMG:1
P2BASEDMG:4
P2DECKCOUNT:0
P2DISCARDCOUNT:2
