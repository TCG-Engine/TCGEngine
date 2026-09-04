# AsUnit_ExhaustTwoGround
#// JTL_210 The Mandalorian — When played as a unit: Exhaust up to 2 ground units. P1 plays it and exhausts
#// both enemy ground units.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: JTL_210
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:1:EXHAUSTED

---

# AsPilot_ExhaustOneEnemyGround
#// JTL_210 The Mandalorian — played as a PILOT (onto a Vehicle), the when-played effect exhausts ONE enemy
#// GROUND unit (vs up to 2 when played as a unit). Piloted onto the friendly AT-ST, P1 exhausts the enemy
#// SOR_046.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: JTL_210
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_210

---

# AsPilot_ExhaustOneEnemySpace
#// JTL_210 The Mandalorian — played as a PILOT onto a SPACE Vehicle, the when-played effect exhausts ONE
#// enemy unit IN THAT ARENA (space), not the ground. Piloted onto the friendly SOR_237 X-Wing, P1 exhausts
#// the enemy space SOR_237.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: JTL_210
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2SPACEARENAUNIT:0:CARDID:SOR_237
P2SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_210


---

# Offer_AsUnit_AllGroundUnitsBothSides
#// JTL_210 The Mandalorian — "When played as a unit: Exhaust up to 2 GROUND units." The clause says
#// "units", not "enemy units", so P1's OWN ground unit (and The Mandalorian himself, who has just
#// entered) belong in the pool alongside the enemy's; the enemy SPACE unit must not. The existing
#// as-unit section answers with two enemy ground units and so cannot detect either a friendly-excluded
#// pool or a leaked space unit. The multi-select is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 14
WithP1Hand: JTL_210
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0

---

# AsPilot_AspectPenaltyUsesThePilotingBracket
#// JTL_210's UNIT side is [Cunning,Cunning]; its Piloting bracket is [2 resources, Cunning] — ONE pip.
#// The deck provides exactly one Cunning (Administrator's Tower base + a Vigilance/Heroism leader), so a
#// PILOT play is fully on-aspect and costs 2. It used to cost 4: SWUAspectPenalty priced every play off
#// CardAspect (the unit side), leaving CardPilotingAspects a generated dictionary with no consumer, and
#// the doubled Cunning charged a +2 penalty that the bracket never asks for. CR 8.19.a — paying the
#// Piloting cost accounts for the aspect penalties that modify THAT cost. JTL_210 is the only card in
#// the pool whose two sides carry different aspect sets, so it is the only card this can be seen on.

## GIVEN
CommonSetup: ybw/bbk
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8:SOR_046:1
WithP1Hand: JTL_210
WithP1GroundArena: SOR_232:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1RESAVAILABLE:6
