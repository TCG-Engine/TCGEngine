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
