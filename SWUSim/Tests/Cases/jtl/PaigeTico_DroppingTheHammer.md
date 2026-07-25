# RoundTrip_UnitToCorvusToEjectToDefeat
#// Captive round-trip: the captive rides the captor through unit → pilot-upgrade → unit. JTL_046 Paige
#// captures SOR_095 (SHD_131); Corvus attaches Paige (captive tucked on the pilot subcard); Eject detaches
#// her back to a ground unit. She comes back EXHAUSTED (proving Eject fired — which is only possible if
#// Corvus first made her a pilot upgrade) and STILL holds SOR_095 as a captive (proving the captive
#// survived both transitions). Corvus is left with no pilot. (Rescue-on-defeat of a captor is covered by
#// shd/Capture_RescueOnCaptorDefeat.md — defeating this Paige would release SOR_095 to P2 exhausted.)

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 20
WithP1Hand: SHD_131 JTL_038 JTL_126
WithP1Deck: SOR_237
WithP1GroundArena: JTL_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:JTL_046
P1GROUNDARENAUNIT:0:EXHAUSTED
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:SOR_095
P1SPACEARENAUNIT:0:CARDID:JTL_038
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# OnAttack_ExpThenSelfDamage
#// JTL_046 Paige Tico (pilot) — Attached gains "On Attack: give an Experience token to this unit, then
#// deal 1 to it." Host SOR_237 power 2 + pilot upgradePower 2 = 4, +Exp token +1 → 5, then 1 self-damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_237:1:0
WithP1SpaceArenaUpgrade: 0:JTL_046

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENAUNIT:0:DAMAGE:1
P1SPACEARENAUNIT:0:POWER:5

---

# AsUnit_NoOnAttackGrant
#// JTL_046 Paige Tico — the "On Attack: give an Experience token, then deal 1 to it" is a PILOTING grant to
#// the attached Vehicle, not one of Paige's own abilities. Seated as a UNIT (so she's ready and can attack),
#// Paige (3/2) attacks the base for 3 with NO Experience token and NO self-damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_046
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:3

---

# PlayedAsPilotFromHand_OnAttackFires
#// JTL_046 Paige Tico — PLAYED from hand with Piloting [2 Vigilance Heroism] onto a friendly Vehicle (the
#// existing section pre-attaches her; this ports the actual play-as-pilot path). She attaches to the ready
#// host SOR_237; when it attacks, the granted On Attack fires: SOR_237 (power 2 + pilot +2 = 4) gets an
#// Experience token (+1/+1 → 5) then takes 1 self-damage.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 8
WithP1Hand: JTL_046
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_237
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_046
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:DAMAGE:1
