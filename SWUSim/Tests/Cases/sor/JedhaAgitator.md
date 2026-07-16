# LeaderUnit_DealsToBase
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
