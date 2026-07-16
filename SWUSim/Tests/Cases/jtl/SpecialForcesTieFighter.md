# NotMore_StaysExhausted
#// JTL_135 Special Forces TIE Fighter — if the opponent does not control more space units, it stays
#// exhausted. With no enemy space units, JTL_135 (P1's only space unit) does not ready.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_135
WithP1Resources: 2

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_135
P1SPACEARENAUNIT:0:EXHAUSTED

---

# OppMoreSpace_Readies
#// JTL_135 Special Forces TIE Fighter — When Played: If an opponent controls more space units than you,
#// ready this unit. P2 has 2 space units; after JTL_135 enters (P1 has 1), 2 > 1 so it readies.

## GIVEN
CommonSetup: grk/bbk/{
  myLeader:JTL_011;
  myBase:JTL_022;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_135
WithP1Resources: 2
WithP2SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_135
P1SPACEARENAUNIT:0:READY
