# OnAttack_ExhaustIndirect
#// JTL_227 Superheavy Ion Cannon — granted "On Attack: You may exhaust a non-leader unit the defending
#// player controls. If you do, deal indirect damage equal to its power to that player." JTL_069 carries
#// the cannon and attacks the P2 base; on attack P1 exhausts SOR_225 (power 2) and deals 2 indirect to P2,
#// who splits it (1 onto SOR_225, which dies; 1 onto the base). Base = 4 (combat) + 1 (indirect) = 5.
#//
#// (This exercises an indirect MZSPLITASSIGN fired INSIDE a mid-combat On Attack — previously deferred
#// as a known engine bug; the session-50 indirect-funnel rework resolved it. This test guards that.)

## GIVEN
CommonSetup: ggw/rrk
WithActivePlayer: 1
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArenaUpgrade: 0:JTL_227
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:mySpaceArena-0:1,myBase-0:1

## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:5

---

# OnAttack_ExhaustIndirectSplit
#// JTL_227 Superheavy Ion Cannon (upgrade on a Capital Ship) — granted On Attack: may exhaust an enemy
#// non-leader unit; if you do, deal indirect to the defending player equal to that unit's power. Host
#// JTL_069 (Capital Ship, 4 power; JTL_227 grants +0 power) attacks P2's base. P1 exhausts P2's SEC_080
#// (power 3) → 3 indirect, which P2 ASSIGNS across a unit AND the base: 1 to their 1-HP SOR_128 (defeats
#// it) + 2 to their base. SEC_080 stays in play (exhausted, undamaged). P2 base = 4 combat + 2 indirect = 6.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP1SpaceArenaUpgrade: 0:JTL_227
WithP2SpaceArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
- P2>AnswerDecision:myGroundArena-0:1,myBase-0:2

## EXPECT
P2GROUNDARENACOUNT:0
P2SPACEARENACOUNT:1
P2BASEDMG:6
P1NODECISION
