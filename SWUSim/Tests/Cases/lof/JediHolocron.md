# OnAttackHeal
#// LOF_051 Jedi Holocron — Attach to a Force unit; attached gains "On Attack: may heal 3 from another
#// unit." Plo Koon (with the Holocron) attacks the base and heals 3 from the damaged friendly SOR_046
#// (5 → 2).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_051
WithP1GroundArena: SOR_046:1:5

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# AttachRestriction_ForceUnitOnly
#// LOF_051 Jedi Holocron — "Attach to a Force unit." Played from hand with TWO Force units (LOF_050 Plo Koon
#// at idx 0, SOR_038 Count Dooku at idx 2) and a non-Force unit (SOR_095 Battlefield Marine at idx 1), only
#// the two Force units are legal attach targets (a lone Force target would auto-resolve, so two are used to
#// force the prompt). Ref: "should only be attached to Force unit".

## GIVEN
CommonSetup: bbw/rrk/{myResources:2;handCardIds:LOF_051}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_038:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2

---

# HealEnemyUnit
#// LOF_051 Jedi Holocron — "heal 3 damage from ANOTHER unit" can target an ENEMY unit, not just a friendly
#// one. Plo Koon (with Holocron) attacks the base; P1 heals 3 from the damaged enemy SOR_046 (4 → 1).

## GIVEN
CommonSetup: bbw/rrk
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_051
WithP2GroundArena: SOR_046:1:4

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
