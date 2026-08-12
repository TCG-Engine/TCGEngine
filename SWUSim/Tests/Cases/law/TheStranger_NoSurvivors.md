# Decline_SimultaneousDamage
#// LAW_086 The Stranger — declining the optional defender-first ordering means combat is the normal
#// SIMULTANEOUS exchange (CR 7.6.3). The Stranger (power 1, undamaged → no Grit yet) deals only 1 to the
#// Marine (3/3, survives), and takes the Marine's 3 counter-damage. Compare the YES case where Grit
#// boosts it to 4 and kills the Marine.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_086:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# DefenderFirst_GritBoostsDamage
#// LAW_086 The Stranger (1/7, Grit) — "While attacking, you may have the defending unit deal combat
#// damage before this unit." This combos with Grit: The Stranger attacks Battlefield Marine (3/3) and
#// chooses defender-first. The Marine deals 3 to The Stranger first (7 HP → survives, 3 damage); Grit then
#// raises The Stranger's power from 1 to 4 (+1 per damage), so it deals 4 to the Marine (3 HP) → defeated.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_086:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LAW_086
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# AttackingBase_NoPrompt
#// LAW_086 The Stranger — the "defender deals damage first" choice only exists when attacking a UNIT.
#// Attacking the enemy base is not a unit, so no prompt appears; The Stranger (power 1) simply deals 1 to
#// the base.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_086:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P2BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:LAW_086
P1NODECISION

---

# DefenderFirst_DefenderSurvives
#// LAW_086 The Stranger (1/7, Grit) — choosing defender-first against SOR_046 Consular Security Force
#// (3/7): the CSF deals 3 to The Stranger first (7 HP → survives, 3 damage); Grit then raises The
#// Stranger's power to 4, so it deals 4 to the CSF (7 HP → survives with 4 damage). Neither unit dies.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_086:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:4

---

# DefenderFirstKillsTheStranger_ItDealsNoDamageBack
#// LAW_086 The Stranger (1/7, Grit) — the RISK side of the defender-first choice. Handing the defender
#// the first strike means The Stranger can die BEFORE it ever swings, and a defeated unit deals no
#// combat damage at all (CR: it is no longer in play to deal it).
#// Pre-damaged to 4, Grit already has it at power 5 — but Battlefield Marine's (3/3) first strike takes
#// it to 7 damage on 7 HP, so it is defeated and the Marine takes ZERO. The DAMAGE:0 is the whole point:
#// Grit raising its power is irrelevant once it is dead, so this must not "trade".

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_086:1:4
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# AlreadyDealsDamageFirst_NoPrompt
#// LAW_086 The Stranger — the offer is "you MAY have the defending unit deal damage first", so it is
#// meaningless when The Stranger is ALREADY dealing its damage first. SOR_217 Shoot First grants exactly
#// that (+1/+0 and "deals its combat damage before the defender"), so no prompt may appear.
#// The Stranger (power 1) becomes 2 and strikes SOR_128 Death Star Stormtrooper (3/1) first, defeating it
#// — so the Stormtrooper's 3 power never lands and The Stranger takes 0. P1NODECISION is the assertion
#// under test; the DAMAGE:0 confirms the deal-first ordering actually applied rather than the prompt
#// merely being skipped.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:SOR_021;
  theirBase:SOR_021;
  myResources:6
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_086:1:0
WithP1Hand: SOR_217
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1NODECISION
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:LAW_086
P1GROUNDARENAUNIT:0:DAMAGE:0

---

# AttackingBase_WithAnotherTriggerInTheWindow_NoOrderPrompt
#// LAW_086 The Stranger attacking a BASE, with a SECOND ability genuinely triggering in the same window.
#// Sharper than AttackingBase_NoPrompt: there, no prompt could also mean "the only trigger auto-resolved".
#// Here P1's base is LOF_029 Crystal Caves ("When a friendly Force unit attacks: create your Force token")
#// and The Stranger has the Force trait, so Crystal Caves DOES fire. If The Stranger's ability were also
#// collected as a trigger when attacking a base, the window would hold TWO and SWUSim would raise an
#// ordering prompt. P1HASFORCE proves Crystal Caves really fired (without it, P1NODECISION would pass
#// vacuously); P1NODECISION proves The Stranger added nothing to the window.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_002;
  myBase:LOF_029;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: LAW_086:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P1HASFORCE
P2BASEDMG:1
P1GROUNDARENAUNIT:0:CARDID:LAW_086
