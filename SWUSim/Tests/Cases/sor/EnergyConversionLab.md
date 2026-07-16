# AmbushTrade
#// SOR_022 Energy Conversion Lab: Epic Action plays BF Marine at printed cost, grants AMBUSH.
#// P1 has exactly 2 resources (printed cost of SOR_095, no aspect penalty with SOR_014+SOR_022).
#// Ambush attack into opponent's ready Marine: both 3/3 units trade. Both arenas empty.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 2:SOR_095
WithP1Hand: SOR_095
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED

---

# ObiWanAmbush
#// SOR_022 ECL: Obi-Wan Kenobi played out-of-aspect, paying printed cost + aspect penalty.
#// SOR_049: cost 6, 4/6, Vigilance+Heroism. With SOR_014 (Aggression/Heroism) + SOR_022 (Command):
#// Heroism covered, Vigilance uncovered → +2 penalty → player pays 8 total.
#// Ambush attack into P2's ready BF Marine (3/3). Obi-Wan (4 power) kills Marine. Takes 3 back.
#// Obi-Wan survives with 3 damage (6 HP). Resources exhausted to 0.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 8:SOR_095
WithP1Hand: SOR_049
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_049
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED

---

# Wilderness_AmbushFirst
#// SOR_022 ECL: Wilderness Fighter (Shielded) played with AMBUSH. Player picks Ambush first.
#// SOR_064: cost 3, 2/4, Shielded, Vigilance aspect. +2 penalty → pays 5.
#// P2 Marine has 1 damage. Wilderness attacks (no shield yet) → Marine dies. Takes 3 back → 3 damage.
#// Shield token then applied after combat. Survives at 3 damage with a fresh shield.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 5:SOR_095
WithP1Hand: SOR_064
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>ResolveTrigger:Ambush
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_064
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:SHIELDCOUNT:1
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED

---

# Wilderness_ShieldFirst
#// SOR_022 ECL: Wilderness Fighter (Shielded) played with AMBUSH. Player declines Ambush.
#// SOR_064: cost 3, 2/4, Shielded, Vigilance aspect. +2 penalty → pays 5.
#// Ambush fires first (auto-dispatch). Player says NO → no attack. Shielded fires next → shield given.
#// Unit survives with 0 damage and 1 shield.

## GIVEN
SkipPreGame: true
CommonSetup: grw/grw/{
  myBase:SOR_022;
  theirBase:SOR_023
}
WithP1Resources: 5:SOR_095
WithP1Hand: SOR_064
WithP2GroundArena: SOR_095:1:1

## WHEN
- P1>UseBaseAbility
- P1>AnswerDecision:myHand-0
- P1>ResolveTrigger:Shielded
- P1>AnswerDecision:YES

## EXPECT
P1RESAVAILABLE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_064
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1BASE:EPICUSED
