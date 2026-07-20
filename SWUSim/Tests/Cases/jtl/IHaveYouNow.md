# VehicleAttack_PreventSelfDamage
#// JTL_193 I Have You Now — Attack with a Vehicle; prevent all damage that would be dealt to it this
#// attack. SOR_237 attacks SOR_044: the defender takes 2, but SOR_237's counter-damage is prevented (0).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_193
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:2

---

# PreventsLethalCounter
#// JTL_193 I Have You Now — the prevention saves the attacker even from a lethal counter. SOR_237 (2/3)
#// attacks SOR_052 (6/9): normally the 6 counter would defeat SOR_237, but all damage to it is prevented,
#// so it survives at 0 damage while SOR_052 takes 2.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_193
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_052:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:2

---

# NonVehicle_NoValidAttacker
#// JTL_193 I Have You Now — the attacker must be a VEHICLE. With only a non-Vehicle unit (SOR_046 Trooper)
#// in play there is no legal attacker: the event fizzles to the discard and no attack occurs.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_193
WithP1Resources: 5
WithP1GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_044:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HANDCOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:JTL_193
P2SPACEARENAUNIT:0:DAMAGE:0
