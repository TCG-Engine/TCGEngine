# OnAttack_Indirect
#// JTL_139 Dengar (pilot) — Attached gains "On Attack: deal 2 indirect to a player (3 if attached is an
#// Underworld unit)." On a non-Underworld host SOR_237 (2+1 power = 3), attacking the base: 3 combat + 2
#// indirect = 5 to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_139

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:5

---

# OnAttack_IndirectSplitUnitAndBase
#// JTL_139 Dengar (pilot) — On Attack: deal 2 indirect to a player (non-Underworld host). With an enemy
#// unit in play the damaged player (P2) ASSIGNS the 2 indirect, splitting it across a unit AND the base:
#// 1 to their 1-HP SOR_128 (defeats it) + 1 to their base. Host SOR_237 (2 power +1 from JTL_139 = 3)
#// attacks P2's base for 3 combat, so P2 base = 3 combat + 1 indirect = 4; SOR_128 is defeated.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_139
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:1,myBase-0:1

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4
P1NODECISION

---

# OnAttack_Indirect3_UnderworldHost
#// JTL_139 Dengar (pilot) — the granted "On Attack: deal 2 indirect to a player" becomes 3 when the
#// attached unit is an UNDERWORLD unit. On SOR_178 Cartel Spacer (Underworld, 2 power → 3 with Dengar),
#// attacking the base with no enemy units: 3 combat + 3 indirect = 6 to P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_178:1:0
WithP1SpaceArenaUpgrade: 0:JTL_139

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:6
