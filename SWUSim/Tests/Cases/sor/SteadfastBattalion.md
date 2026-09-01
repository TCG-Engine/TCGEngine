# OnAttack_NoLeaderUnit_NoBuff
#// COVERAGE: offer=OnAttack_Offer_IncludesLeaderUnit (pending choice, exact pool incl. the leader unit)
#//           decline=N/A (the buff is not "you may" — with the condition met the give is mandatory;
#//           condition-off branch is OnAttack_NoLeaderUnit_NoBuff) · boundary=OnAttack_WithLeaderUnit_Buffs
#//           (buff persists across the next action for the buffed unit's own attack: 5+6=11)
#//           · control=PilotLeaderUnit_EnablesBuff (leader-unit status derived from a pilot upgrade;
#//           the plain-attach negative branch is withheld pending an engine fix — the deployed-flag
#//           check counts a plain pilot attach as a leader unit) · reqboundary=OnAttack_WithLeaderUnit_Buffs
#//           (the phase buff survives the action boundary between the two attacks)
#// SOR_116 Steadfast Battalion — absence guard for the conditional On Attack buff.
#// P1's leader is NOT deployed (no leader unit controlled) → condition fails → NO buff.
#// Steadfast Battalion stays 5/5 and its attack on the enemy base deals 5 (printed power).

## GIVEN
CommonSetup: ggw/grw
SkipPreGame: true
WithP1GroundArena: SOR_116:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_116
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:0:HP:5
P1GROUNDARENAUNIT:0:EXHAUSTED
P2BASEDMG:5

---

# OnAttack_WithLeaderUnit_Buffs
#// SOR_116 Steadfast Battalion — Unit 5/5, Ground, Overwhelm.
#// "On Attack: If you control a leader unit, give a friendly unit +2/+2 for this phase."
#// P1 controls a REAL deployed leader unit (Leia @1) → condition met. "A friendly unit" includes the
#// leader unit, so the buff is a genuine 2-target choice; here it's put on Leia (myGroundArena-1).
#// SOR_116 attacks the base for 5; Leia (4 power → 6 with +2/+2) then attacks for 6 → base takes 11.

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:SOR_009:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_116:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P2BASEDMG:11

---

# OnAttack_Offer_IncludesLeaderUnit
#// SOR_116 Steadfast Battalion — the buff target pool is EVERY friendly unit, including the leader
#// unit that satisfied the condition and the attacker itself. P1 controls SOR_116 (attacker),
#// a Battlefield Marine, and deployed Leia (leader unit, ground idx 2). On attack the choose is left
#// PENDING so the exact legal-target set can be asserted: all three friendly units, nothing of P2's.

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:SOR_009:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_116:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&myGroundArena-2

---

# PilotLeaderUnit_EnablesBuff
#// SOR_116 Steadfast Battalion — the condition also holds when the leader unit is DERIVED: a leader
#// deployed as a Pilot upgrade (JTL_008 Wedge on an AT-ST) makes the host a leader unit, so
#// "you control a leader unit" is met without a standalone deployed leader. The buff pool is the
#// host (leader unit) plus SOR_116; the buff is put on SOR_116 itself → it attacks the base for 7.

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:JTL_008;
  myLeaderDeployedPilot:true
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_232:1:0
WithP1GroundArena: SOR_116:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:ISLEADERUNIT
P1GROUNDARENAUNIT:1:CARDID:SOR_116
P1GROUNDARENAUNIT:1:POWER:7
P2BASEDMG:7

---

# PoePlainAttach_NotALeaderUnit_NoBuff
#// JTL_013 Poe Dameron's flip-and-attach makes his host an UPGRADE carrier, NOT a leader unit (his
#// attach text lacks "attached unit is a leader unit"). "While you control a leader unit" is
#// therefore FALSE: the Battalion attacks with no buff choose and the base takes the printed 5.

## GIVEN
CommonSetup: ggw/rrk/{myLeader:JTL_013}
P1OnlyActions: true
WithP1SpaceArena: SOR_225:1:0
WithP1GroundArena: SOR_116:1:0
WithP2GroundArena: SOR_164:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AttackGroundArena:0:BASE

## EXPECT
P1NODECISION
P2BASEDMG:5
P1SPACEARENAUNIT:0:NOTLEADERUNIT

---

# SimulateRequestBoundary_PhaseBuffSurvives
#// SOR_116 Steadfast Battalion — the On Attack "give a friendly unit +2/+2" choose ends the request in
#// production, so the target answer arrives in a fresh process. Mirrors OnAttack_WithLeaderUnit_Buffs
#// with the boundary inserted before the answer: the pending give-buff context must survive the
#// round-trip, and the resulting for-this-phase +2/+2 must still be on Leia for her own later attack
#// (4 + 2 = 6, so the base takes 5 + 6 = 11).

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:SOR_009:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_116:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>SimulateRequestBoundary
- P1>AnswerDecision:myGroundArena-1
- P1>AttackGroundArena:1:BASE

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P2BASEDMG:11

---

# Overwhelm_ExcessSpillsToBase
#// SOR_116 Steadfast Battalion — the OVERWHELM clause, which had no section of its own.
#// "Overwhelm (When attacking an enemy unit, deal excess damage to the opponent's base.)"
#// The 5/5 Battalion attacks a 3/1 Death Star Stormtrooper: 1 damage is lethal, so the other 4 spill
#// onto P2's base. The defender still deals its own 3 back. P1's leader is NOT deployed, so the On
#// Attack buff condition is false and the excess is computed off the printed 5 power.

## GIVEN
CommonSetup: ggw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_116:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:4
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:EXHAUSTED
P1NODECISION

---

# Overwhelm_NoExcessWhenDefenderAbsorbsAll
#// SOR_116 Steadfast Battalion — the NEGATIVE that proves the Overwhelm spill is bounded by "excess":
#// only damage BEYOND what defeats the defender reaches the base. Consular Security Force is 3/7, so
#// all 5 of the Battalion's damage stays on the defender (which survives at 5 damage) and P2's base
#// takes ZERO. Boundary partner of Overwhelm_ExcessSpillsToBase (defender HP 1 → 4 spills;
#// defender HP 7 → 0 spills).

## GIVEN
CommonSetup: ggw/grw
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_116:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:5
P2BASEDMG:0
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# Overwhelm_UsesBuffedPower_BothClausesInteract
#// SOR_116 Steadfast Battalion — the two printed clauses meet: the On Attack "+2/+2 for this phase"
#// resolves during the attack (it is an On Attack trigger, so it lands before combat damage), and
#// Overwhelm then spills the EXCESS of the BUFFED power, not the printed 5. P1 controls a deployed
#// Leia (leader unit) so the condition is met; the buff is put on the Battalion itself → 7 power vs a
#// 3/1 Stormtrooper → 1 lethal + 6 excess to P2's base.

## GIVEN
CommonSetup: ggw/grw/{
  myLeader:SOR_009:1:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_116:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_116
P1GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENACOUNT:0
P2BASEDMG:6
