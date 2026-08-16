# OnAttackSpaceDebuffGroundBuff
#// LAW_068 Millennium Falcon (2/5, space) — On Attack: you may give a space unit -2/-0 for this phase;
#// you may give a ground unit +2/+0 for this phase. Debuff enemy SOR_237 (2/3 -> 0/3), buff friendly
#// SEC_080 (3/3 -> 5/3).

## GIVEN
CommonSetup: brk/bgw/{}
P1OnlyActions: true
WithP1SpaceArena: LAW_068:1:0
WithP2SpaceArena: SOR_237:1:0
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P2SPACEARENAUNIT:0:POWER:0
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:5

---

# SpaceOfferIsEveryUnitInTheSpaceArenaIncludingItself
#// "You may give A SPACE UNIT -2/-0" is unqualified — friendly or enemy, and the Falcon itself is a legal
#// target. Offer: LAW_068 (self), SOR_178 Cartel Spacer (friendly), SHD_187 Lurking TIE Phantom (enemy).
#// SHD_187's "can't be captured, damaged, or defeated by enemy card abilities" does not protect it from
#// a debuff. The choice is left pending so the offer is what's asserted.

## GIVEN
CommonSetup: brk/bgw/{myLeader:SOR_006:1:1:1; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1SpaceArena: [LAW_068:1:0 SOR_178:1:0]
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_187:1:0
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE

## EXPECT
P1DECISIONTOOLTIP:Choose_a_space_unit
P1SELECTABLEEXACT:mySpaceArena-0&mySpaceArena-1&theirSpaceArena-0

---

# GroundOfferIsEveryUnitInTheGroundArenaIncludingBothDeployedLeaders
#// The +2/+0 half is likewise unqualified, and a DEPLOYED LEADER is a unit: the offer is SOR_095
#// Battlefield Marine and P1's deployed SOR_006 Palpatine, plus P2's SHD_029 Pyke Sentinel and P2's
#// deployed SOR_010 Darth Vader.

## GIVEN
CommonSetup: brk/bgw/{myLeader:SOR_006:1:1:1; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1SpaceArena: [LAW_068:1:0 SOR_178:1:0]
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_187:1:0
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0

## EXPECT
P1DECISIONTOOLTIP:Choose_a_ground_unit
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&theirGroundArena-0&theirGroundArena-1

---

# DebuffItselfAndBuffAnEnemyDeployedLeader
#// Both halves aimed at the "wrong" side on purpose: the Falcon debuffs ITSELF to 0 power and buffs the
#// ENEMY's deployed Darth Vader to 7. The debuff lands before combat damage, so the Falcon's attack on
#// P2's base deals 0.

## GIVEN
CommonSetup: brk/bgw/{myLeader:SOR_006:1:1:1; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1SpaceArena: [LAW_068:1:0 SOR_178:1:0]
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_187:1:0
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1SPACEARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:1:POWER:7
P2BASEDMG:0

---

# PassingBothHalvesChangesNothing
#// Both halves are "you may" and independent — declining both leaves every power on the board at its
#// printed value (Falcon 2, Cartel Spacer 2, Lurking TIE Phantom 2, Battlefield Marine 3, Palpatine 4,
#// Pyke Sentinel 2, Darth Vader 5) and the attack still lands its 2 on P2's base.

## GIVEN
CommonSetup: brk/bgw/{myLeader:SOR_006:1:1:1; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1SpaceArena: [LAW_068:1:0 SOR_178:1:0]
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_187:1:0
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-
- P1>AnswerDecision:-

## EXPECT
P1SPACEARENAUNIT:0:POWER:2
P1SPACEARENAUNIT:1:POWER:2
P2SPACEARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:4
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:1:POWER:5
P2BASEDMG:2

---

# PassingOnlyTheSpaceHalfStillAllowsTheGroundBuff
#// Declining the debuff must not swallow the buff: no space power changes, Battlefield Marine still
#// gets +2/+0.

## GIVEN
CommonSetup: brk/bgw/{myLeader:SOR_006:1:1:1; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1SpaceArena: [LAW_068:1:0 SOR_178:1:0]
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_187:1:0
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1SPACEARENAUNIT:0:POWER:2
P2SPACEARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:POWER:5
P1GROUNDARENAUNIT:1:POWER:4
P2GROUNDARENAUNIT:1:POWER:5
P2BASEDMG:2

---

# PassingOnlyTheGroundHalfStillAppliesTheSpaceDebuff
#// The mirror: take the debuff, decline the buff. Lurking TIE Phantom drops to 0 and every ground power
#// stays printed.

## GIVEN
CommonSetup: brk/bgw/{myLeader:SOR_006:1:1:1; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1SpaceArena: [LAW_068:1:0 SOR_178:1:0]
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_187:1:0
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:-

## EXPECT
P2SPACEARENAUNIT:0:POWER:0
P1SPACEARENAUNIT:0:POWER:2
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:1:POWER:4
P2GROUNDARENAUNIT:0:POWER:2
P2GROUNDARENAUNIT:1:POWER:5
P2BASEDMG:2

---

# DebuffItselfAndBuffAnEnemyDeployedLeader_SurvivesTheRequestBoundary
#// LAW_068 — request-boundary guard for DebuffItselfAndBuffAnEnemyDeployedLeader: same fixture, same flow,
#// one extra SimulateRequestBoundary inserted BETWEEN the two halves (before the ground answer). This is
#// the valuable insertion point for this card: by then the -2/-0 "for this phase" is already applied to
#// the Falcon and the second half of the On Attack is still queued, so both the phase-scoped stat effect
#// and the pending continuation must survive serialization — production starts a FRESH process on every
#// answered decision. After the boundary the Falcon must still be at 0 power (so its attack deals 0 to
#// P2's base) and the +2/+0 must still land on the enemy deployed Vader.
#// The insertion point is a genuine 4-option MZMAYCHOOSE (both ground arenas incl. both deployed
#// leaders), so the boundary is not vacuous.

## GIVEN
CommonSetup: brk/bgw/{myLeader:SOR_006:1:1:1; theirLeader:SOR_010:1:1:1}
P1OnlyActions: true
WithP1SpaceArena: [LAW_068:1:0 SOR_178:1:0]
WithP1GroundArena: SOR_095:1:0
WithP2SpaceArena: SHD_187:1:0
WithP2GroundArena: SHD_029:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0
- P1>SimulateRequestBoundary
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P1SPACEARENAUNIT:0:POWER:0
P2GROUNDARENAUNIT:1:POWER:7
P2BASEDMG:0
