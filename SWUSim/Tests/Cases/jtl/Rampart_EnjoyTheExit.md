# LowPower_DoesntReady
#// JTL_182 Rampart — This unit doesn't ready during the regroup phase unless its power is 4 or more.
#// Rampart (3 power) starts exhausted and stays EXHAUSTED through regroup, while the control SOR_237
#// readies normally.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1SpaceArena: JTL_182:0:0
WithP1SpaceArena: SOR_237:0:0

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_182
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:1:CARDID:SOR_237
P1SPACEARENAUNIT:1:READY

---

# HighPower_Readies
#// JTL_182 Rampart — the "doesn't ready" restriction lifts once its power is 4 or more. An SOR_120 upgrade
#// (+2/+2) makes Rampart a 5-power unit, so it DOES ready during the regroup phase.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1SpaceArena: JTL_182:0:0
WithP1SpaceArenaUpgrade: 0:SOR_120

## WHEN
- P1>Pass
- P2>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_182
P1SPACEARENAUNIT:0:POWER:5
P1SPACEARENAUNIT:0:READY

---

# LastingEffectExpires_DoesntReady
#// JTL_182 Rampart — a temporary +2/+2 (SOR_124 Tactical Advantage, "for this phase") lifts him to 5 power
#// during the action phase, but the boost EXPIRES at end of the action phase, so at regroup his power is
#// back to 3 and he does NOT ready — while the control SOR_237 readies normally.

## GIVEN
CommonSetup: gbk/gbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 10
WithP1Hand: SOR_124
WithP1SpaceArena: JTL_182:0:0
WithP1SpaceArena: SOR_237:0:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P2>Pass
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
P1SPACEARENAUNIT:0:CARDID:JTL_182
P1SPACEARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:POWER:3
P1SPACEARENAUNIT:1:CARDID:SOR_237
P1SPACEARENAUNIT:1:READY
