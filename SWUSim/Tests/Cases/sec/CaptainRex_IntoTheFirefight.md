# AttackEnd_SelfAndEnemySentinel
#// SEC_048 (Ground, 7/7) — When this unit completes an attack: give this unit AND an enemy unit
#//   Sentinel for this phase. SEC_048 attacks P2's base; on attack-end it gains Sentinel and grants the
#//   only enemy unit (SOR_046) Sentinel too.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_048:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:7
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenPlayed_SelfAndEnemySentinel
#// SEC_048 (Ground, 7/7, cost 6, Vigilance/Heroism) — When Played: give this unit AND an enemy unit
#//   Sentinel for this phase. P1 plays SEC_048 (on-aspect under bw leader → cost 6); the only enemy
#//   unit (SOR_046) auto-resolves as the Sentinel target.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6}
WithActivePlayer: 1
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_048

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# WhenPlayed_MultipleEnemies_TargetEnemyLeaderUnit
#// SEC_048 Captain Rex — the "give an enemy unit Sentinel" target may be ANY enemy unit across arenas,
#//   including an enemy leader UNIT. With three enemies present — a ground unit (SOR_164 Wampa), a space
#//   unit (LOF_131 Strikeship), and P2's deployed leader unit (SOR_005 Luke) — all three are selectable;
#//   choosing Luke gives Sentinel to Luke and to Rex.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;theirLeader:SOR_005:1:1:1}
WithActivePlayer: 1
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: LOF_131:1:0
WithP1Hand: SEC_048

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0

---

# WhenPlayed_TargetEnemyLeaderUnit_GrantsSentinel
#// SEC_048 Captain Rex — continuation: choosing P2's deployed leader unit (SOR_005 Luke) gives Sentinel
#//   to Luke and to Rex.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;theirLeader:SOR_005:1:1:1}
WithActivePlayer: 1
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: LOF_131:1:0
WithP1Hand: SEC_048

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:1:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# AttackComplete_TargetUnitThatAlreadyHasSentinel
#// SEC_048 Captain Rex — an enemy unit that ALREADY has Sentinel (SOR_099 Bright Hope, innate) is still a
#//   legal target of the "give an enemy unit Sentinel" clause. Rex attacks P2's base; on completing the
#//   attack, both a ground enemy (SOR_164 Wampa) and the space Bright Hope are selectable. Choosing Bright
#//   Hope re-applies Sentinel to it and grants Rex Sentinel.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_048:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: SOR_099:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P2BASEDMG:7
P2SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# AttackNotCompleted_NoSentinel
#// SEC_048 Captain Rex — the on-attack clause is "when this unit COMPLETES an attack". Rex (7/7) attacks
#//   an SOR_039 AT-AT Suppressor (8/8) and is defeated by the return damage, so the attack is not
#//   completed: no Sentinel is granted and Rex goes to the discard pile.

## GIVEN
CommonSetup: bbw/rrk/{myResources:4}
WithActivePlayer: 1
WithP1GroundArena: SEC_048:1:0
WithP2GroundArena: SOR_039:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
