# WhenDefeated_HealUnit
#// JTL_071 CR90 Relief Runner — When Defeated: Heal up to 3 damage from a unit or base. JTL_071 (4/6,
#// pre-damaged to 1 remaining) attacks SOR_225 and is defeated by the counter; its When Defeated heals 3
#// from the damaged SOR_046 (3 → 0). (Restore 2 heals P1's undamaged base on attack — no effect.)

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_071:1:5
WithP1GroundArena: SOR_046:1:3
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:3

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# WhenDefeated_HealBase
#// JTL_071 CR90 Relief Runner — the When Defeated heal can target a BASE. CR90 (pre-damaged to 1 remaining)
#// attacks and dies to the counter. Restore 2 first heals P1's base (5 → 3 damage) on attack; then the
#// When Defeated heals 3 from P1's base (3 → 0).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  myBaseDamage:5;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_071:1:5
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myBase-0
- P1>AnswerDecision:3

## EXPECT
P1SPACEARENACOUNT:0
P1BASEDMG:0

---

# WhenDefeated_HealUndamagedNoEffect
#// JTL_071 CR90 Relief Runner — a no-damage target may be chosen for the heal, producing no effective
#// healing. CR90 (pre-damaged to 1 remaining) attacks SOR_225 and dies to the counter; its When Defeated
#// heal is directed at P1's undamaged base, which stays at 0 damage (SOR_046 keeps its 4 damage).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_071:1:5
WithP1GroundArena: SOR_046:1:4
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myBase-0

## EXPECT
P1SPACEARENACOUNT:0
P1BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:4

---

# WhenDefeated_HealLessThanMax
#// JTL_071 CR90 Relief Runner — "Heal UP TO 3" lets the controller heal FEWER than 3. CR90 dies to the
#// counter; its When Defeated targets the 4-damage SOR_046 but the controller chooses to heal only 1, so
#// SOR_046 drops from 4 to 3 damage (not to 1).

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_071:1:5
WithP1GroundArena: SOR_046:1:4
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:1

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# WhenDefeated_DeclineHealsNothing
#// JTL_071 CR90 Relief Runner — the When Defeated heal is a MAY (canChooseNoTargets): the controller may
#// decline entirely even with a damaged target available. CR90 dies; P1 declines the heal, so the damaged
#// SOR_046 keeps all 4 damage.

## GIVEN
CommonSetup: bbw/bbk/{
  myLeader:JTL_004;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1SpaceArena: JTL_071:1:5
WithP1GroundArena: SOR_046:1:4
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:PASS

## EXPECT
P1SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:DAMAGE:4
