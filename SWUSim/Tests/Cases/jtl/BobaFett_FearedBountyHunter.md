# AsUpgrade_TransportDeal2
#// JTL_189 Boba Fett (pilot) — When played as an upgrade: you may deal 1 damage to a unit (2 if the
#// attached unit is a Transport). Played onto JTL_186 (Transport), it deals 2 to SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_189
WithP1SpaceArena: JTL_186:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# AsPilot_NonTransport_Deal1
#// JTL_189 Boba Fett — as a pilot, the damage is only 2 on a TRANSPORT host; on a non-Transport Vehicle
#// (SOR_237 Alliance X-Wing) it deals just 1. P1 pilots Boba onto SOR_237 and deals 1 to SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_189
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# AsUnit_NoDamage
#// JTL_189 Boba Fett — the damage is a WHEN-PLAYED-AS-A-PILOT (upgrade) ability. Played as a normal UNIT
#// it deals nothing: SOR_046 stays undamaged.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 10
WithP1Hand: JTL_189
WithP1SpaceArena: SOR_237:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Unit

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
