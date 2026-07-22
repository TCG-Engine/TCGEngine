# AttackThenExhaust
#// JTL_228 Barrel Roll — Attack with a space unit; after completing the attack, you may exhaust a space
#// unit. SOR_237 hits the enemy base for 2, then P1 exhausts the enemy space unit SOR_044.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_228
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2BASEDMG:2
P2SPACEARENAUNIT:0:EXHAUSTED

---

# CantAttack_PlayAnyway
#// JTL_228 Barrel Roll — "Attack with a space unit …". With no READY friendly space unit to attack with,
#// the event does nothing and is played anyway (to the discard). SOR_237 is already exhausted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_228
WithP1Resources: 5
WithP1SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1SPACEARENAUNIT:0:EXHAUSTED
P1DISCARDCOUNT:1

---

# ExhaustEvenIfAttackerDefeated
#// JTL_228 Barrel Roll — the "then exhaust a space unit" happens even if the attacker is defeated by the
#// counter. SOR_237 (2/3) attacks JTL_069 (4/7): it deals 2 but dies to the 4-power counter. Barrel Roll
#// then still exhausts a space unit — P1 exhausts the surviving enemy JTL_069.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_228
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: JTL_069:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:0
P2SPACEARENAUNIT:0:CARDID:JTL_069
P2SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:0:EXHAUSTED
