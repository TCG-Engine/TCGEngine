# OnAttack_Heal
#// JTL_066 Trace Martez (pilot) — Attached gains "On Attack: you may heal 2 total from any number of
#// units." The host (SOR_225 + pilot) attacks the base; the granted On Attack heals SOR_046 (3 → 1).

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaUpgrade: 0:JTL_066
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1

---

# OnAttack_AssignZeroHealing
#// JTL_066 Trace Martez — the granted On Attack heal is "up to 2 from ANY NUMBER of units", so P1 may
#// assign 0. Declining leaves the damaged SOR_046 at its full 3 damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0
WithP1SpaceArenaUpgrade: 0:JTL_066
WithP1GroundArena: SOR_046:1:3

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
