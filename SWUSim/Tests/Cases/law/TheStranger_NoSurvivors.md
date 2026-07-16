# Decline_SimultaneousDamage
#// LAW_086 The Stranger — declining the optional defender-first ordering means combat is the normal
#// SIMULTANEOUS exchange (CR 7.6.3). The Stranger (power 1, undamaged → no Grit yet) deals only 1 to the
#// Marine (3/3, survives), and takes the Marine's 3 counter-damage. Compare the YES case where Grit
#// boosts it to 4 and kills the Marine.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_086:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# DefenderFirst_GritBoostsDamage
#// LAW_086 The Stranger (1/7, Grit) — "While attacking, you may have the defending unit deal combat
#// damage before this unit." This combos with Grit: The Stranger attacks Battlefield Marine (3/3) and
#// chooses defender-first. The Marine deals 3 to The Stranger first (7 HP → survives, 3 damage); Grit then
#// raises The Stranger's power from 1 to 4 (+1 per damage), so it deals 4 to the Marine (3 HP) → defeated.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_086:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_086
P1GROUNDARENAUNIT:0:DAMAGE:3
