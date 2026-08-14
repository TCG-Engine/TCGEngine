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
