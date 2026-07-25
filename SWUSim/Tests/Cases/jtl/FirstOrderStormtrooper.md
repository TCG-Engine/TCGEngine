# OnAttack_Indirect
#// JTL_132 First Order Stormtrooper — On Attack: 1 indirect to a player. The trooper (power 2) attacks
#// P2's base for 2 and deals 1 more indirect, totalling 3.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_132:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Opponent

## EXPECT
P2BASEDMG:3

---

# OnAttack_IndirectToUnit
#// JTL_132 First Order Stormtrooper — On Attack: 1 indirect to a player. 1 damage can't split across two
#// targets, but this verifies the assigner may put it on a UNIT instead of the base. With an enemy unit
#// in play P2 assigns the 1 indirect to their 1-HP SOR_128 (defeats it) rather than the base. The trooper
#// (power 2) attacks P2's base for 2 combat; the indirect goes to the unit, so P2 base = 2 (combat only).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_132:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:Opponent
- P2>AnswerDecision:myGroundArena-0:1

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:2
P1NODECISION

---

# WhenDefeated_Indirect
#// JTL_132 First Order Stormtrooper — When Defeated: deal 1 indirect to a player. (This card has both an
#// On Attack and a When Defeated ability; this section covers the When Defeated half.) P2 (active) attacks
#// the 2/1 trooper with SOR_128 (3/1): they trade — the trooper dies, and its 2 combat damage defeats
#// the 1-HP SOR_128. The trooper's controller P1 then resolves its When Defeated for 1 indirect to the
#// opponent; with no surviving P2 unit it lands on P2's base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: JTL_132:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P2>AttackGroundArena:0:theirGroundArena-0
- P1>Drain
- P1>AnswerDecision:Opponent

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P2BASEDMG:1
P1NODECISION
