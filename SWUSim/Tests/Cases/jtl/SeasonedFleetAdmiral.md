# OppDraw_GiveExp
#// JTL_111 Seasoned Fleet Admiral — When an opponent draws 1+ cards during the action phase, you may give
#// an Experience token to a unit. P1 plays a filler, then SOR_190 (Lothal Insurgent) makes P2 draw; P1's
#// Admiral reacts and gives an Experience token to itself (1/4 → 2/5).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_111:1:0
WithP1Hand: SOR_063
WithP1Hand: SOR_190
WithP1Resources: 12
WithP2Hand: SOR_095
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2

---

# Raid1_AttackBonus
#// JTL_111 Seasoned Fleet Admiral has Raid 1 — while attacking it gets +1/+0. The 1-power Admiral attacks
#// P2's base for 2.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_111:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2

---

# OppDraw_DeclineExp
#// JTL_111 Seasoned Fleet Admiral — the give-Experience reaction is a MAY. P2 draws (via SOR_190), P1's
#// Admiral reacts but DECLINES (Pass), so no Experience is given and it stays at 1 power.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_111:1:0
WithP1Hand: SOR_063
WithP1Hand: SOR_190
WithP1Resources: 12
WithP2Hand: SOR_095
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1

---

# OwnDraw_NoTrigger
#// JTL_111 Seasoned Fleet Admiral — the reaction is "When an OPPONENT draws"; your OWN draws do NOT
#// trigger it. P1 plays SOR_171 Mission Briefing and chooses "You", so P1 (the Admiral's controller)
#// draws 2 cards. No opponent drew, so the Admiral offers no give-Experience decision and stays at 1 power.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_111:1:0
WithP1Hand: SOR_171
WithP1Resources: 12
WithP1Deck: SOR_128
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:You

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1
P1NODECISION

---

# OppMultiDraw_OneExp
#// JTL_111 Seasoned Fleet Admiral — "When an opponent draws 1 or MORE cards during the action phase:
#// you may give an Experience token to a unit." The reaction is once per draw-EVENT, not per card. P1
#// plays SOR_171 Mission Briefing choosing "Opponent", so P2 draws 2 cards at once. The Admiral fires
#// exactly ONCE; P1 gives the Experience to the Admiral (1 power → 2). Only one decision, so afterward
#// P1NODECISION holds (no second Experience prompt for the 2nd card).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_111:1:0
WithP1Hand: SOR_171
WithP1Resources: 12
WithP2Deck: SOR_128
WithP2Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:Opponent
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:2
P1NODECISION

---

# RegroupDraw_NoTrigger
#// JTL_111 Seasoned Fleet Admiral — the reaction is action-phase-only. Both players pass to reach the
#// regroup phase; at the regroup draw step P2 (the opponent) draws its 2 cards, but because it is NOT
#// the action phase the Admiral does not fire — it stays at 1 power with no give-Experience decision.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: JTL_111:1:0
WithP1Deck: [SOR_128 SOR_128 SOR_128]
WithP2Deck: [SOR_128 SOR_128 SOR_128]

## WHEN
- P1>Pass
- P2>Pass

## EXPECT
P1GROUNDARENAUNIT:0:POWER:1
