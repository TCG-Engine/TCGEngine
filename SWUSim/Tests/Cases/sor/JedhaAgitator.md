# LeaderUnit_DealsToBase
#// COVERAGE: offer=Offer_GroundUnitsAndBasesOnly (exact pool: all ground units both sides + both
#//           bases; space excluded) · decline=N/A (the deal-2 is mandatory once the leader-unit gate
#//           holds; gate-off branch asserted via P1NODECISION in NoLeaderUnit_NoEffect)
#//           control=N/A (gate reads "you control a leader unit" live at attack time; no per-unit
#//           marker to survive a control change) · boundary=N/A (no duration effect; the ability is
#//           one-shot inside a single attack) · reqboundary=LeaderUnit_DealsToBase /
#//           SelfTarget_DiesAndAttackDoesNotResolve (the target answer arrives in a separate request
#//           from the attack declaration)
#//
#// SOR_158 Jedha Agitator — the On Attack target can be a BASE instead of a unit. Jedha attacks the
#// enemy ground unit (combat → 2 to LAW_124, Jedha 2/1 dies to the 4-power counter); its On Attack
#// deals 2 to the enemy base. Combat went to the unit, so the base's 2 damage is purely the ability —
#// proving the base branch. P1 also controls a deployed leader unit (Sabine @1), which survives — so
#// after Jedha (@0) dies P1 still has 1 ground unit.

## GIVEN
CommonSetup: rrw/rrk/{
  myLeader:SOR_014:1:1:1;
  theirBase:SOR_027
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_158:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENACOUNT:1

---

# LeaderUnit_DealsToGroundUnit
#// SOR_158 Jedha Agitator (Aggression unit, cost 2, 2/1, Rebel) — "Saboteur. On Attack: If you control
#// a leader unit, deal 2 damage to a ground unit or a base." P1 controls a deployed leader unit
#// (Sabine @1) so the condition holds. Jedha (@0) attacks the enemy base (combat → 2 to base); its
#// On Attack deals 2 to the enemy ground unit LAW_124 (4/7 → survives at DAMAGE:2).

## GIVEN
CommonSetup: rrw/rrk/{
  myLeader:SOR_014:1:1:1;
  theirBase:SOR_027
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_158:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2
P2BASEDMG:2
P1GROUNDARENAUNIT:1:ISLEADERUNIT

---

# NoLeaderUnit_NoEffect
#// SOR_158 Jedha Agitator — the On Attack is gated on "If you control a leader unit." With NO deployed
#// leader, the ability does nothing: Jedha's attack deals only its combat damage to the base, no target
#// choice is offered, and the enemy unit is untouched.

## GIVEN
CommonSetup: rrw/rrk/{
  theirBase:SOR_027
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_158:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:2
P2GROUNDARENAUNIT:0:DAMAGE:0
P1NODECISION

---

# Offer_GroundUnitsAndBasesOnly
#// SOR_158 Jedha Agitator — the On Attack target pool is exactly "a ground unit or a base": every
#// ground unit on BOTH sides (including Jedha itself and P1's deployed leader unit) plus both bases.
#// P2's SPACE unit is excluded. The choice is left pending so the exact offer can be asserted.

## GIVEN
CommonSetup: rrw/rrk/{
  myLeader:SOR_014:1:1:1;
  theirBase:SOR_027
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_158:1:0
WithP2GroundArena: LAW_124:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&myBase-0&theirBase-0

---

# SelfTarget_DiesAndAttackDoesNotResolve
#// SOR_158 Jedha Agitator — the ability can target Jedha itself. Attacking the enemy base, P1 puts
#// the 2 damage on Jedha (2/1): he is defeated during his own On Attack, so the attack does NOT
#// resolve — the enemy base takes NO combat damage. Only the deployed leader (Sabine) remains on
#// P1's ground arena.

## GIVEN
CommonSetup: rrw/rrk/{
  myLeader:SOR_014:1:1:1;
  theirBase:SOR_027
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_158:1:0
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2BASEDMG:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1DISCARDCOUNT:1

---

# PilotLeaderUpgrade_HostIsLeaderUnit_AbilityWorks
#// SOR_158 Jedha Agitator — "you control a leader unit" is satisfied by a PILOT leader upgrade whose
#// text makes the attached unit a leader unit (JTL_018 Kazuda: "Attached unit is a leader unit").
#// Kazuda deploys as a Pilot onto the lone friendly Vehicle (SOR_237 in space); Jedha then attacks
#// the enemy base and the On Attack fires: 2 (combat) + 2 (ability, aimed at the base) = 4.

## GIVEN
CommonSetup: rrw/yyw/{
  myLeader:JTL_018;
  theirBase:SOR_027
}
SkipPreGame: true
P1OnlyActions: true
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_158:1:0

## WHEN
- P1>DeployLeader
- P1>AnswerDecision:Pilot
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:ISLEADERUNIT
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:EXHAUSTED
