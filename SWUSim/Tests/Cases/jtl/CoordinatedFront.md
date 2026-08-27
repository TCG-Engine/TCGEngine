# BuffGroundAndSpace
#// JTL_253 Coordinated Front (event) — You may give a ground unit +2/+2 and a space unit +2/+2 this
#// phase. P1 buffs SOR_095 (ground, 3/3 → 5/5) and SOR_237 (space, 2/3 → 4/5).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_253
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:5

---

# OnlyGround_DeclineSpace
#// JTL_253 Coordinated Front — each half is independent ("You MAY give"). P1 buffs the ground unit but
#// declines the space half (Pass): SOR_095 becomes 5/5 while SOR_237 stays at its printed 2/3.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_253
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:3

---

# DeclineBoth_NoBuff
#// JTL_253 Coordinated Front — both halves declined (Pass, Pass): the event resolves with no buffs.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_253
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:0:HP:3

---

# OnlySpace_DeclineGround
#// JTL_253 Coordinated Front — the mirror of OnlyGround: each half is independent ("You MAY give"). P1
#// declines the ground half (choose-nothing) but buffs the space unit: SOR_237 becomes 4/5 while ground SOR_095
#// stays at its printed 3/3.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_253
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:5

---

# SimulateRequestBoundary_BetweenGroundAndSpaceHalves
#// JTL_253 Coordinated Front — a two-step chain: the ground pick ends the request, and the space pick ends
#// another, so in production the second half of the event resolves in a process that never saw the first.
#// The continuation (that a space half is still owed) AND the phase-duration ground buff already applied
#// must both survive serialization. Mirrors BuffGroundAndSpace with a boundary before EACH answer.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_253
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:5

---

# OnlySpace_DeclineGround_DeclinedWithPASS
#// Byte-for-byte twin of OnlySpace_DeclineGround, answering **PASS** — the real client's decline token
#// for an MZMAYCHOOSE. The two grants are INDEPENDENT ("You may … / You may …"), but the CUSTOM that runs
#// the SPACE half was unflagged, so a sticky PASS on the GROUND half swallowed it: the space unit was
#// never offered and the event did half of what it says. The "-" twin cannot see this — "-" is not
#// sticky. Fixed with dontSkipOnPass: 1 on the JTL_253#0 continuation.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_253
WithP1Resources: 2
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_237:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1SPACEARENAUNIT:0:POWER:4
P1SPACEARENAUNIT:0:HP:5
