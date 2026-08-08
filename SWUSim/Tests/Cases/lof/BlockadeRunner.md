# BaseDamage_Exp
#// LOF_166 Blockade Runner (4 power) — Saboteur + "When this unit deals combat damage to a base: may give
#// an Experience token to this unit." It attacks the base (4 damage) and gains an Experience token.

## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_166:1:0

## WHEN
- P1>AttackSpaceArena:0:BASE
- P1>AnswerDecision:YES

## EXPECT
P2BASEDMG:4
P1SPACEARENAUNIT:0:UPGRADECOUNT:1

---

# AttackUnit_NoExp
#// LOF_166 — the Experience trigger is "deals combat damage to a BASE"; attacking an enemy unit (Concord
#// Dawn Interceptors, Sentinel) deals no base damage, so no Experience token is given. Saboteur ignores the
#// Sentinel. Blockade Runner (4/4) kills Concord (4 HP) and survives its 3 counter (1 power +2 defending);
#// no Saboteur/Overwhelm spill, so P2's base takes 0 and Blockade gains no Experience.
## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_166:1:0
WithP2SpaceArena: SHD_042:1:0
## WHEN
- P1>AttackSpaceArena:0:theirSpaceArena-0
## EXPECT
P1SPACEARENAUNIT:0:UPGRADECOUNT:0
P1SPACEARENAUNIT:0:DAMAGE:3
P2SPACEARENACOUNT:0
P2BASEDMG:0

---

# EnemyAttacksBase_NoExp
#// LOF_166 — the trigger is only on THIS unit's own attack. When the ENEMY Concord Dawn attacks P1's base,
#// Blockade Runner does not trigger and gains no Experience.
## GIVEN
CommonSetup: rrw/rrk
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1SpaceArena: LOF_166:1:0
WithP2SpaceArena: SHD_042:1:0
## WHEN
- P2>AttackSpaceArena:0:BASE
## EXPECT
P1BASEDMG:1
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# FriendlyOtherUnitAttacksBase_NoExp
#// LOF_166 — a DIFFERENT friendly unit (a ground Battlefield Marine) attacking the base does not trigger
#// Blockade Runner's ability; it gains no Experience.
## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1SpaceArena: LOF_166:1:0
WithP1GroundArena: SOR_095:1:0
## WHEN
- P1>AttackGroundArena:0:BASE
## EXPECT
P2BASEDMG:3
P1SPACEARENAUNIT:0:UPGRADECOUNT:0

---

# OverwhelmToBase_Exp
#// LOF_166 — the Experience trigger also fires when combat damage reaches the base via Overwhelm. Blockade
#// Runner (4/4) wears Heroic Resolve (+1/+1 →5/5); its granted action pays 2, defeats the upgrade (→4/4),
#// and attacks with +4/+0 and Overwhelm (8 power) into Concord Dawn (4 HP). 4 defeats Concord and 4
#// overwhelms to P2's base, so Blockade Runner gains an Experience token. Intended: "should get an Experience
#// token when damaging the base with Overwhelm".
## GIVEN
CommonSetup: rrw/rrk
P1OnlyActions: true
WithP1Resources: 2
WithP1SpaceArena: LOF_166:1:0
WithP1SpaceArenaUpgrade: 0:SHD_155
WithP2SpaceArena: SHD_042:1:0
## WHEN
- P1>UseUnitAbility:mySpaceArena-0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:YES
## EXPECT
P2SPACEARENACOUNT:0
P2BASEDMG:4
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
