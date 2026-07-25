# OnAttack_GiveAnother
#// ASH_157 Danger Squadron Wingmen (Space, 4/5) — On Attack: you may give an Advantage token to another
#// unit. Attacks P2's base; gives an Advantage token to a friendly Marine (another unit).
## GIVEN
CommonSetup: rrw/rrk
WithP1SpaceArena: ASH_157:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:1

---

# OnAttack_Decline
#// ASH_157 Danger Squadron Wingmen — the Advantage grant is optional. Declining gives no token; the attack
#// still deals 4 to the base.
## GIVEN
CommonSetup: rrw/rrk
WithP1SpaceArena: ASH_157:1:0
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:-
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:0:ADVANTAGECOUNT:0

---

# OnAttack_GiveFriendlySpace
#// ASH_157 Danger Squadron Wingmen — "another unit" is ANY other unit in play. Attacking P2's base, P1
#// gives the Advantage token to the other friendly space unit SOR_237.
## GIVEN
CommonSetup: rrw/rrk/{myLeader:SOR_001:1:1;theirLeader:SOR_002:1:1}
WithP1SpaceArena: ASH_157:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:mySpaceArena-1
## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:1:CARDID:SOR_237
P1SPACEARENAUNIT:1:ADVANTAGECOUNT:1
P1SPACEARENAUNIT:0:ADVANTAGECOUNT:0

---

# OnAttack_GiveFriendlyLeader
#// ASH_157 Danger Squadron Wingmen — the Advantage token may go to a friendly leader unit (deployed
#// SOR_001 in the ground arena).
## GIVEN
CommonSetup: rrw/rrk/{myLeader:SOR_001:1:1;theirLeader:SOR_002:1:1}
WithP1SpaceArena: ASH_157:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:myGroundArena-1
## EXPECT
P2BASEDMG:4
P1GROUNDARENAUNIT:1:ISLEADERUNIT
P1GROUNDARENAUNIT:1:ADVANTAGECOUNT:1

---

# OnAttack_GiveEnemyGround
#// ASH_157 Danger Squadron Wingmen — "another unit" is not limited to friendly units; P1 may hand the
#// Advantage token to an ENEMY ground unit (SEC_080).
## GIVEN
CommonSetup: rrw/rrk/{myLeader:SOR_001:1:1;theirLeader:SOR_002:1:1}
WithP1SpaceArena: ASH_157:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:ADVANTAGECOUNT:1

---

# OnAttack_GiveEnemySpace
#// ASH_157 Danger Squadron Wingmen — the token may go to an enemy space unit (SOR_060).
## GIVEN
CommonSetup: rrw/rrk/{myLeader:SOR_001:1:1;theirLeader:SOR_002:1:1}
WithP1SpaceArena: ASH_157:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2BASEDMG:4
P2SPACEARENAUNIT:0:CARDID:SOR_060
P2SPACEARENAUNIT:0:ADVANTAGECOUNT:1

---

# OnAttack_GiveEnemyLeader
#// ASH_157 Danger Squadron Wingmen — the token may even go to an enemy leader unit (deployed SOR_002).
## GIVEN
CommonSetup: rrw/rrk/{myLeader:SOR_001:1:1;theirLeader:SOR_002:1:1}
WithP1SpaceArena: ASH_157:1:0
WithP1SpaceArena: SOR_237:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
WithP2SpaceArena: SOR_060:1:0
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:theirGroundArena-1
## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:1:ISLEADERUNIT
P2GROUNDARENAUNIT:1:ADVANTAGECOUNT:1
