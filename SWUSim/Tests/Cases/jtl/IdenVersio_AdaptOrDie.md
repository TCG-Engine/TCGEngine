# OnAttach_GivesShield
#// JTL_036 Iden Versio — OnAttached: give a Shield token to the host Vehicle.
#// Piloting [3], Vigilance+Villainy. Leader SOR_002 (Vigilance+Villainy) + Base SOR_019 (Vigilance).
#// Aspect penalty = 0. Pilot cost = 3. Resources = 3 (exactly enough).
#// SOR_225 (TIE/ln Fighter, power=2, hp=1). JTL_036 upgradePower=3, upgradeHp=3 → host 5/4.
#// After attach: host has JTL_036 as upgrade (index 0), shield token (index 1). Host SHIELDCOUNT=1.
#// The unit-side Shielded keyword on JTL_036 does NOT fire (JTL_036 enters as an upgrade, not a unit).

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_019;
  theirBase:SOR_019
}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 3
WithP1Hand: JTL_036
WithP1SpaceArena: SOR_225:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1SPACEARENACOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:2
P1SPACEARENAUNIT:0:UPGRADE:0:CARDID:JTL_036
P1SPACEARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:HP:4
P1HANDCOUNT:0
P1RESAVAILABLE:0

---

# AsUnit_Shielded
#// JTL_036 Iden Versio — played as a UNIT (no friendly Vehicle to pilot), her Shielded keyword gives her a
#// Shield token as she enters. Iden (4/3) enters P1's ground arena with SHIELDCOUNT 1.

## GIVEN
CommonSetup: bbk/bbk/{
  myBase:SOR_019;
  theirBase:SOR_019
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 4
WithP1Hand: JTL_036

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:JTL_036
P1GROUNDARENAUNIT:0:HASKEYWORD:Shielded
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1

---

# ReAttach_ReShields_AfterLeaveAndReEnter
#// JTL_036 Iden Versio — her OnAttached "give the host a Shield" ability re-registers correctly after she
#// leaves and re-enters play. P1 plays Iden as a Pilot on SOR_225 (tieln) → tieln gets Iden + a Shield. P2's
#// Bamboozle (SOR_199) exhausts tieln and returns its upgrades to hand — Iden goes back to P1's hand and
#// tieln is left bare. P1 replays Iden as a Pilot, this time onto SOR_232 (AT-ST): the OnAttached fires
#// AGAIN, so AT-ST gets Iden + a fresh Shield (proving the trigger was not left dangling on the old host).

## GIVEN
CommonSetup: bbk/bbk/{myBase:SOR_019;theirBase:SOR_019}
SkipPreGame: true
WithActivePlayer: 1
WithInitiativePlayer: 1
WithP1Resources: 12
WithP2Resources: 12
WithP1Hand: JTL_036
WithP2Hand: SOR_199
WithP1SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_232:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0
- P2>PlayHand:0
- P2>AnswerDecision:theirSpaceArena-0
- P1>PlayHand:0
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_232
P1GROUNDARENAUNIT:0:UPGRADECOUNT:2
P1GROUNDARENAUNIT:0:UPGRADE:0:CARDID:JTL_036
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P1SPACEARENAUNIT:0:CARDID:SOR_225
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
