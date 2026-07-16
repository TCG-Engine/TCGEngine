# IgnoreAspectPenalty_OfficialUnit
#// SEC_009 Mon Mothma — Ignore the aspect penalties on non-Villainy Official units you play.
#// P1's leader is SEC_009 (Command/Heroism), base JTL_019 (Vigilance). SEC_163 (Fringe/Official, Aggression,
#// cost 2) is off-aspect → normally +2 penalty (cost 4). With exactly 2 resources it would be unplayable,
#// but Mon Mothma zeroes the penalty → it plays for 2 (RESAVAILABLE:0). Its "may defeat an upgrade" When
#// Played fizzles (no upgrades in play).

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:SEC_009;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 2
WithP1Hand: SEC_163

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SEC_163
P1RESAVAILABLE:0
