# OnAttackDefeatCreditDeal
#// LAW_191 Arvel Skeen (4/3) — When Played/On Attack: you may defeat a Credit token (any player's). If
#// you do, deal 1 damage to a unit or base. Attacks the base; defeat P2's Credit -> deal 1 to P2's base
#// (base: 4 combat + 1 = 5).

## GIVEN
CommonSetup: rrw/bgw/{theirResources:0}
P1OnlyActions: true
WithP2Credits: 1
WithP1GroundArena: LAW_191:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirResources-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:5
P2CREDITCOUNT:0

---

# WhenPlayed_FriendlyCredit_DamageSelf
#// LAW_191 Arvel Skeen — When Played: "you may defeat a Credit token (any player's). If you do, deal 1
#// damage to a unit or base." Played from hand while both players hold 3 Credits. P1 declines the
#// Credit-payment discount (pays full), defeats one of its OWN Credits, then deals the 1 damage to Arvel
#// himself. P1 ends with 2 Credits, P2 keeps 3, Arvel has 1 damage.

## GIVEN
CommonSetup: rrw/bgw/{theirResources:0}
P1OnlyActions: true
WithP1Resources: 6
WithP1Credits: 3
WithP2Credits: 3
WithP1Hand: LAW_191
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE
- P1>AnswerDecision:myResources-6
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P1CREDITCOUNT:2
P2CREDITCOUNT:3

---

# WhenPlayed_EnemyCredit_DamageEnemyUnit
#// LAW_191 Arvel Skeen — When Played: defeat an ENEMY Credit and deal 1 to an enemy unit. P1 defeats one
#// of P2's Credits (P1 keeps its own 3), then deals 1 to SOR_095 Battlefield Marine. P2 drops to 2 Credits.

## GIVEN
CommonSetup: rrw/bgw/{theirResources:0}
P1OnlyActions: true
WithP1Resources: 6
WithP1Credits: 3
WithP2Credits: 3
WithP1Hand: LAW_191
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE
- P1>AnswerDecision:theirResources-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P1CREDITCOUNT:3
P2CREDITCOUNT:2

---

# WhenPlayed_FriendlyCredit_DamageFriendlyUnit
#// LAW_191 Arvel Skeen — When Played: defeat a friendly Credit, deal 1 to a friendly unit. P1 defeats its
#// own Credit and deals 1 to SOR_164 Wampa. P1 ends with 2 Credits; P2 has none.

## GIVEN
CommonSetup: rrw/bgw/{theirResources:0}
P1OnlyActions: true
WithP1Resources: 6
WithP1Credits: 3
WithP1Hand: LAW_191
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE
- P1>AnswerDecision:myResources-6
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1CREDITCOUNT:2
P2CREDITCOUNT:0

---

# WhenPlayed_FriendlyCredit_DamageFriendlyBase
#// LAW_191 Arvel Skeen — When Played: defeat a friendly Credit, deal 1 to FRIENDLY base. P1 ends with 2
#// Credits and 1 damage on its own base.

## GIVEN
CommonSetup: rrw/bgw/{theirResources:0}
P1OnlyActions: true
WithP1Resources: 6
WithP1Credits: 3
WithP1Hand: LAW_191
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE
- P1>AnswerDecision:myResources-6
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:1
P1CREDITCOUNT:2

---

# WhenPlayed_FriendlyCredit_DamageEnemyBase
#// LAW_191 Arvel Skeen — When Played: defeat a friendly Credit, deal 1 to the ENEMY base. P1 ends with 2
#// Credits; P2's base has 1 damage.

## GIVEN
CommonSetup: rrw/bgw/{theirResources:0}
P1OnlyActions: true
WithP1Resources: 6
WithP1Credits: 3
WithP1Hand: LAW_191
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:DONE
- P1>AnswerDecision:myResources-6
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:1
P1CREDITCOUNT:2

---

# WhenPlayed_Declined_NothingHappens
#// LAW_191 Arvel Skeen — the ability is optional ("you may"). P1 has no Credits, P2 has 3. Arvel is played
#// but P1 declines to defeat any Credit, so no damage is dealt and P2 keeps all 3 Credits.

## GIVEN
CommonSetup: rrw/bgw/{theirResources:0}
P1OnlyActions: true
WithP1Resources: 6
WithP2Credits: 3
WithP1Hand: LAW_191
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:PASS

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:0
P2BASEDMG:0
P2CREDITCOUNT:3
P1CREDITCOUNT:0

---

# WhenPlayed_NobodyHasCredits_NoOp
#// LAW_191 Arvel Skeen — with no Credits anywhere there is nothing to defeat, so the ability does nothing
#// and no decision is offered. Arvel enters with no damage dealt.

## GIVEN
CommonSetup: rrw/bgw/{theirResources:0}
P1OnlyActions: true
WithP1Resources: 6
WithP1Hand: LAW_191
WithP1GroundArena: SOR_164:1:0
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P1BASEDMG:0
P2BASEDMG:0
P1CREDITCOUNT:0
P2CREDITCOUNT:0
P1NODECISION

---

# OnAttack_FriendlyCredit_DamageSelf
#// LAW_191 Arvel Skeen — On Attack: defeat a friendly Credit, deal 1 to Arvel himself. Arvel attacks the
#// enemy base; P1 defeats its own Credit and deals the 1 to Arvel. P1 ends with 2 Credits, P2 keeps 3,
#// Arvel has 1 damage (plus enemy base takes the 4 combat damage).

## GIVEN
CommonSetup: rrw/bgw/{myResources:0; theirResources:0}
P1OnlyActions: true
WithP1Credits: 3
WithP2Credits: 3
WithP1GroundArena: [SOR_164:1:0 LAW_191:1:0]
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:1
P2BASEDMG:4
P1CREDITCOUNT:2
P2CREDITCOUNT:3

---

# OnAttack_EnemyCredit_DamageEnemyUnit
#// LAW_191 Arvel Skeen — On Attack: defeat an ENEMY Credit, deal 1 to an enemy unit. Arvel attacks the
#// base (4 combat); P1 defeats one of P2's Credits and deals 1 to SOR_095 Battlefield Marine. P2 → 2
#// Credits, P1 keeps 3.

## GIVEN
CommonSetup: rrw/bgw/{myResources:0; theirResources:0}
P1OnlyActions: true
WithP1Credits: 3
WithP2Credits: 3
WithP1GroundArena: [SOR_164:1:0 LAW_191:1:0]
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:theirResources-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:4
P1CREDITCOUNT:3
P2CREDITCOUNT:2

---

# OnAttack_FriendlyCredit_DamageFriendlyUnit
#// LAW_191 Arvel Skeen — On Attack: defeat a friendly Credit, deal 1 to a friendly unit (SOR_164 Wampa).
#// Arvel attacks the base (4 combat); P1 → 2 Credits, P2 keeps 3, Wampa has 1 damage.

## GIVEN
CommonSetup: rrw/bgw/{myResources:0; theirResources:0}
P1OnlyActions: true
WithP1Credits: 3
WithP2Credits: 3
WithP1GroundArena: [SOR_164:1:0 LAW_191:1:0]
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P2BASEDMG:4
P1CREDITCOUNT:2
P2CREDITCOUNT:3

---

# OnAttack_FriendlyCredit_DamageFriendlyBase
#// LAW_191 Arvel Skeen — On Attack: defeat a friendly Credit, deal 1 to FRIENDLY base. Arvel attacks the
#// enemy base (4 combat there) while its own base takes the 1. P1 → 2 Credits, P2 keeps 3.

## GIVEN
CommonSetup: rrw/bgw/{myResources:0; theirResources:0}
P1OnlyActions: true
WithP1Credits: 3
WithP2Credits: 3
WithP1GroundArena: [SOR_164:1:0 LAW_191:1:0]
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:myBase-0

## EXPECT
P1BASEDMG:1
P2BASEDMG:4
P1CREDITCOUNT:2
P2CREDITCOUNT:3

---

# OnAttack_FriendlyCredit_DamageEnemyBase
#// LAW_191 Arvel Skeen — On Attack: defeat a friendly Credit, deal 1 to the ENEMY base on top of the 4
#// combat damage → 5 total on P2's base. P1 → 2 Credits, P2 keeps 3.

## GIVEN
CommonSetup: rrw/bgw/{myResources:0; theirResources:0}
P1OnlyActions: true
WithP1Credits: 3
WithP2Credits: 3
WithP1GroundArena: [SOR_164:1:0 LAW_191:1:0]
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:myResources-0
- P1>AnswerDecision:theirBase-0

## EXPECT
P2BASEDMG:5
P1CREDITCOUNT:2
P2CREDITCOUNT:3

---

# OnAttack_Declined_OnlyCombatDamage
#// LAW_191 Arvel Skeen — On Attack the ability is optional. Arvel attacks the base for 4 combat damage;
#// P1 declines to defeat a Credit, so no extra damage and Credits are unchanged (base takes only 4).

## GIVEN
CommonSetup: rrw/bgw/{myResources:0; theirResources:0}
P1OnlyActions: true
WithP1Credits: 3
WithP2Credits: 3
WithP1GroundArena: [SOR_164:1:0 LAW_191:1:0]
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>AttackGroundArena:1:BASE
- P1>AnswerDecision:PASS

## EXPECT
P2BASEDMG:4
P1CREDITCOUNT:3
P2CREDITCOUNT:3

---

# OnAttack_NobodyHasCredits_OnlyCombat
#// LAW_191 Arvel Skeen — On Attack with no Credits anywhere: nothing to defeat, no decision offered, and
#// the base takes only the 4 combat damage.

## GIVEN
CommonSetup: rrw/bgw/{myResources:0; theirResources:0}
P1OnlyActions: true
WithP1GroundArena: [SOR_164:1:0 LAW_191:1:0]
WithP1SpaceArena: SOR_212:1:0
WithP2GroundArena: SOR_095:1:0
WithP2SpaceArena: SOR_134:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:4
P1CREDITCOUNT:0
P2CREDITCOUNT:0
P1NODECISION
