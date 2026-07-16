# Deploy_OnAttack_Decline
#// SHD_012 Bo-Katan Kryze — Deployed: OnAttack declined → no damage beyond combat.
#// No other Mandalorian attacked, so only the first "deal 1" question fires.

## GIVEN
CommonSetup: rrw/ggw/{
  myLeader:SHD_012
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6:SOR_095
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EPICUSED

---

# Deploy_OnAttack_DoubleHit
#// SHD_012 Bo-Katan Kryze — Deployed: Both OnAttack hits fire when another Mandalorian attacked first.
#// SOR_162 (Fang Fighter, Mandalorian Space unit) attacks base first,
#// then Bo-Katan attacks — total Mandalorian attacks >= 2 → second ability available.

## GIVEN
CommonSetup: rrw/ggw/{
  myLeader:SHD_012
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1Resources: 6:SOR_095
WithP1SpaceArena: SOR_162:1:0
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackSpaceArena:0:BASE
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:7
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EPICUSED

---

# Deploy_OnAttack_NoDoubleHit_AfterBravado
#// SHD_012 Bo-Katan + SHD_182 Bravado — same Mandalorian attacks twice, second OnAttack
#// ability must NOT fire. "Another Mandalorian unit" requires a different unit (uid != attacker).
#// Bravado paid at full 5 (no enemy defeated this phase).

## GIVEN
CommonSetup: rrw/ggw/{
  myLeader:SHD_012
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 11:SOR_095
WithP1Hand: SHD_182
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:8
P2GROUNDARENAUNIT:0:DAMAGE:2
P1LEADER:EPICUSED
P1RESAVAILABLE:6

---

# Deploy_OnAttack_SingleHit
#// SHD_012 Bo-Katan Kryze — Deployed: OnAttack YES first hit only (no other Mandalorian attacked).

## GIVEN
CommonSetup: rrw/ggw/{
  myLeader:SHD_012
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1Resources: 6:SOR_095
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>DeployLeader
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:4
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EPICUSED

---

# LeaderAction_MandalorianAttacked
#// SHD_012 Bo-Katan Kryze — Leader Action: Mandalorian attacked → deal 1 to a unit.
#// SOR_162 (Disabling Fang Fighter) is Mandalorian trait, Space arena.

## GIVEN
CommonSetup: rrw/ggw/{
  myLeader:SHD_012
}
SkipPreGame: true
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithActivePlayer: 1
WithP1SpaceArena: SOR_162:1:0
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>UseLeaderAbility
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2BASEDMG:3
P2GROUNDARENAUNIT:0:DAMAGE:1
P1LEADER:EXHAUSTED

---

# LeaderAction_NoMandalorianAttacked
#// SHD_012 Bo-Katan Kryze — Leader Action: No Mandalorian attacked → exhaust only, no damage.

## GIVEN
CommonSetup: rrw/ggw/{
  myLeader:SHD_012
}
SkipPreGame: true
WithP2GroundArena: SOR_095:2:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1LEADER:EXHAUSTED
