# BountyBadge_Exhausted
#// SHD_033 Synara San — an EXHAUSTED Synara shows the Bounty badge (conditional keyword active
#// while Status 0 = exhausted).

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SHD_033:0:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Bounty

---

# BountyBadge_OnlyWhileExhausted
#// SHD_033 Synara San — a READY Synara shows NO Bounty badge (the conditional keyword is
#// exhausted-only; Status 1 = ready, 0 = exhausted). Guards the Status check in
#// HasConditionalKeyword_Bounty.

## GIVEN
CommonSetup: grw/grw
WithP1GroundArena: SHD_033:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:NOTKEYWORD:Bounty

---

# ExhaustedDefeated_Bounty5ToBase
#// SHD_033 Synara San — "While this unit is exhausted, she gains 'Bounty — Deal 5 damage to a base.'"
#// P2's exhausted, 2-damaged Synara (3/6 Grit) is defeated by Industrious Team (LAW_124 4/7: 4 dmg →
#// 6 total = HP). P1 collects the conditional bounty and deals 5 to P2's base. Grit counter: Synara's
#// power at damage time is 3 + 2 damage = 5 onto the attacker.

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SHD_033:0:2    # exhausted, 2 damage

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:5
P1GROUNDARENAUNIT:0:DAMAGE:5

---

# ReadyDefeated_NoBounty
#// SHD_033 Synara San — the Bounty exists ONLY while she is exhausted. A READY Synara defeated in
#// combat offers no bounty: no decision pending, no base damage. (Absence guard for the conditional.)

## GIVEN
CommonSetup: grw/grw
P1OnlyActions: true
WithP1GroundArena: LAW_124:1:0
WithP2GroundArena: SHD_033:1:2    # ready, 2 damage

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0
P1NODECISION
