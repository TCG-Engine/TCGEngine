# UseForce_HealBase
#// LOF_102 Yoda's Lightsaber — When Played: may use the Force → heal 3 damage from a base. P1 plays it onto
#// SOR_095, uses the Force, and heals P1's own base (5 → 2).

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_102;myBaseDamage:5}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P1NOFORCE
P1BASEDMG:2

---

# UseForce_HealEnemyBase
#// LOF_102 Yoda's Lightsaber — the heal-3-from-a-base may target EITHER base. Attached to SOR_095, P1 uses the
#// Force and heals the enemy base (3 → 0). Intended: "should heal enemy base".

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_102;theirBaseDamage:3}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:theirBase-0

## EXPECT
P1NOFORCE
P2BASEDMG:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# DeclineForce_NoHeal
#// LOF_102 Yoda's Lightsaber — using the Force is a "may". P1 declines: the Force token is kept, no base is
#// healed (own base stays at 5 damage), and the saber remains attached. Intended: "decides not to use the
#// Force".

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_102;myBaseDamage:5}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P1HASFORCE
P1BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# NoForceToken_NoHeal
#// LOF_102 Yoda's Lightsaber — with no Force token controlled, the When-Played heal cannot be used: no prompt,
#// no heal (own base stays at 5), the saber still attaches. Intended: "doesn't have the Force".

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_102;myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1NODECISION
P1BASEDMG:5
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1

---

# AttachToEnemyUnit
#// LOF_102 Yoda's Lightsaber — "Attach to a non-Vehicle unit" allows an ENEMY host (CR 2.e). With both a
#// friendly host (SOR_046) and an enemy host (SOR_095) present, P1 chooses the ENEMY, then still uses the
#// Force to heal its OWN base (the When-Played belongs to the caster regardless of host controller). The
#// enemy unit carries the saber; the friendly host does not. Intended: allows attaching to an enemy unit.

## GIVEN
CommonSetup: bbw/rrk/{myResources:6;handCardIds:LOF_102;myBaseDamage:5}
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P1NOFORCE
P1BASEDMG:2
P2GROUNDARENAUNIT:0:UPGRADECOUNT:1
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
