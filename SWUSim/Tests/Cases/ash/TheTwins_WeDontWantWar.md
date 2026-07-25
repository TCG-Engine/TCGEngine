# FriendlyDefeatedHeal
#// ASH_127 The Twins — "When another friendly unit is defeated: heal 1 from your base." P1's base starts
#// at 2 damage; the friendly SOR_128 (3/1) attacks SEC_080 (3/3) and dies to the counter — a friendly unit
#// was defeated, so The Twins heals 1 from the base (2 → 1).
## GIVEN
CommonSetup: ggk/ggk/{myBaseDamage:2}
WithP1GroundArena: ASH_127:1:0
WithP1GroundArena: SOR_128:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:1

---

# GrantSentinel
#// ASH_127 The Twins (Ground, 2/7, cost 4) — When Played: you may give another friendly unit Sentinel for
#// this phase. P1 plays The Twins and gives SOR_095 Sentinel.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_127}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# GrantSentinel_Pass
#// ASH_127 The Twins — the Sentinel grant is optional. P1 plays The Twins and declines; SOR_095 gains
#// nothing.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_127}
WithP1GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel

---

# SelfDefeated_NoHeal
#// ASH_127 The Twins — the heal is for ANOTHER friendly unit's defeat, not its own. A near-dead Twins dies
#// attacking SOR_046, but since it is the defeated unit itself, no heal occurs (base stays at 2).
## GIVEN
CommonSetup: ggk/ggk/{myBaseDamage:2}
WithP1GroundArena: ASH_127:1:6
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:2

---

# EnemyUnitDefeated_NoHeal
#// ASH_127 The Twins — only a FRIENDLY unit's defeat heals. P1's SOR_046 kills the enemy SOR_128 (3/1);
#// an enemy dying is not friendly, so no heal (base stays at 2).
## GIVEN
CommonSetup: ggk/ggk/{myBaseDamage:2}
WithP1GroundArena: ASH_127:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:2

---

# GrantSentinel_Space
#// ASH_127 The Twins — "another friendly unit" for the Sentinel grant may be a SPACE unit. P1 plays The
#// Twins (to ground) and grants Sentinel to the friendly space unit SOR_110 Frontline Shuttle.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_127}
WithP1SpaceArena: SOR_110:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_110
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel

---

# GrantSentinelOnAttack_Ground
#// ASH_127 The Twins — the Sentinel grant also fires On Attack (not only When Played). A seated Twins
#// attacks P2's base and grants Sentinel to the friendly ground unit LOF_094 Jedi Consular.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: LOF_094:1:0
WithP1GroundArena: ASH_127:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_094
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel
P2BASEDMG:2

---

# GrantSentinelOnAttack_Space
#// ASH_127 The Twins — On Attack, the grant may target a friendly SPACE unit. Seated Twins attacks P2's
#// base and grants Sentinel to SOR_110 Frontline Shuttle in space.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_127:1:0
WithP1SpaceArena: SOR_110:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:CARDID:SOR_110
P1SPACEARENAUNIT:0:HASKEYWORD:Sentinel
P2BASEDMG:2

---

# Pass_OnAttack
#// ASH_127 The Twins — the On Attack Sentinel grant is optional. Seated Twins attacks P2's base and
#// declines; the friendly unit gains nothing.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: LOF_094:1:0
WithP1GroundArena: ASH_127:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_094
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2BASEDMG:2

---

# FriendlyLeaderDefeated_Heal
#// ASH_127 The Twins — a friendly LEADER unit's defeat also heals. P1's deployed leader SOR_011 (3/6,
#// pre-damaged to 1 HP) attacks the enemy SOR_164 Wampa (4/5) and dies to the 4 counter. A friendly unit
#// (the leader) was defeated → The Twins heals 1 (base 2 → 1). The leader flips back off the board.
#// (SOR_011's own On Attack "may deal 1 to a friendly unit" is declined.)
## GIVEN
CommonSetup: ggk/ggk/{myBaseDamage:2;myLeader:SOR_011:1:1:0:5:0}
WithP1GroundArena: ASH_127:1:0
WithP2GroundArena: SOR_164:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENACOUNT:1
P1BASEDMG:1

---

# EnemyLeaderDefeated_NoHeal
#// ASH_127 The Twins — an ENEMY leader unit's defeat does NOT heal. P2's deployed leader SOR_011 (3/6,
#// pre-damaged to 1 HP) is killed by P1's SOR_119 Reinforcement Walker (6/9). Only friendly defeats heal,
#// so the base stays at 2.
## GIVEN
CommonSetup: ggk/ggk/{myBaseDamage:2;theirLeader:SOR_011:1:1:0:5:0}
WithP1GroundArena: ASH_127:1:0
WithP1GroundArena: SOR_119:1:0
P1OnlyActions: true
## WHEN
- P1>AttackGroundArena:1:0
## EXPECT
P2GROUNDARENACOUNT:0
P1BASEDMG:2

