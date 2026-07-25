# MoreSpace_Deal4
#// JTL_125 Air Superiority — If you control more space units than an opponent, deal 4 damage to a ground
#// unit that opponent controls. P1 has a space unit (P2 none), so it deals 4 to SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_125
WithP1Resources: 6
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# NotMoreSpace_NoDamage
#// JTL_125 Air Superiority — the "if you control more space units than an opponent" gate FAILS: P1 has
#// zero space units while P2 has one (SOR_237). No damage is dealt; the event resolves with no effect and
#// goes to the discard, and the enemy ground unit SOR_046 is untouched. (Playing it anyway with the gate
#// failed deals no damage.)

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_125
WithP1Resources: 6
WithP2SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1NODECISION
