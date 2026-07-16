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
