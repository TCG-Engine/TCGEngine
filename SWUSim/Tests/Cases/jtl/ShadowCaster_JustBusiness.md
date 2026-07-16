# ReuseWhenDefeated
#// JTL_169 Shadow Caster — When a friendly unit is defeated: you may use all of its
#// "When Defeated" abilities again.
#// JTL_087 dies attacking SOR_044 → its When Defeated creates a TIE (use #1); Shadow Caster
#// lets P1 use it again → a 2nd TIE (use #2). Arena = Shadow Caster + 2 TIEs = 3.

## GIVEN
CommonSetup: gbk/bbk/{
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_087:1:1
WithP1SpaceArena: JTL_169:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1SPACEARENACOUNT:3

---

# DoesNotReuseEnemyWhenDefeated
#// JTL_169 Shadow Caster reuses only FRIENDLY When Defeated abilities. P1's Daring Raid (TWI_170) defeats
#// P2's Rhokai Gunship (SHD_164, "When Defeated: deal 1 to a unit or base"). That When Defeated belongs to
#// P2 (its controller), so Shadow Caster does not offer P1 a reuse — Rhokai's ability fires exactly once
#// (P2 points it at P1's base → 1 damage), and P1 gets no reuse prompt.

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
SkipPreGame: true
WithActivePlayer: 1
WithP1SpaceArena: JTL_169:1:0
WithP1Hand: TWI_170
WithP2SpaceArena: SHD_164:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P2>Drain
- P2>AnswerDecision:theirBase-0

## EXPECT
P2SPACEARENACOUNT:0
P1SPACEARENACOUNT:1
P1BASEDMG:1
P1NODECISION

---

# ReuseClonedWhenDefeated
#// JTL_169 Shadow Caster reuses a When Defeated the dying unit gained by being a COPY. P1's Clone (TWI_116)
#// enters as a copy of OOM-Series Officer (TWI_131, "When Defeated: deal 2 to a base"). P2's Daring Raid
#// (TWI_170) defeats the Clone; its copied When Defeated deals 2 to P2's base, then Shadow Caster (friendly)
#// lets P1 use it again for 2 more → 4 total. The Clone's controller (P1) is non-active, so the trigger is
#// drained with P1>Drain.

## GIVEN
CommonSetup: ggk/rrk/{myResources:8;theirResources:3}
SkipPreGame: true
WithActivePlayer: 1
WithP1Hand: TWI_116
WithP1GroundArena: TWI_131:1:0
WithP1SpaceArena: JTL_169:1:0
WithP2Hand: TWI_170

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-1
- P1>Drain
- P1>AnswerDecision:theirBase-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P1GROUNDARENACOUNT:1
