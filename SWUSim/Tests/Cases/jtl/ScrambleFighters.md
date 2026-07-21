# Create8ReadyCantAttackBases
#// JTL_092 Scramble Fighters (event) — Create 8 TIE Fighter tokens and ready them; they can't attack
#// bases for this phase. Eight readied TIEs are created, and a TIE attacking the enemy base is a no-op.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_092
WithP1Resources: 7

## WHEN
- P1>PlayHand:0
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENACOUNT:8
P1SPACEARENAUNIT:0:READY
P2BASEDMG:0

---

# DoubledByMoffJerjerrod_16TIEs
#// JTL_092 Scramble Fighters — Moff Jerjerrod (ASH_094): "If you would create a number of tokens, you may
#// defeat this unit. If you do, create twice that number instead." Playing Scramble prompts to defeat Moff;
#// answering YES defeats him (to the discard) and creates 16 TIE Fighters instead of 8.

## GIVEN
CommonSetup: ggk/bbk/{
  myLeader:JTL_005;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_092
WithP1Resources: 7
WithP1GroundArena: ASH_094:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:16
P1GROUNDARENACOUNT:0
