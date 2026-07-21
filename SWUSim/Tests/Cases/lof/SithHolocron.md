# DealAndBuff
#// LOF_138 Sith Holocron — attached gains "On Attack: may deal 2 to a friendly unit. If you do, this unit
#// gets +2/+0 for this attack." Plo Koon (6 + 1 from the +1/+1 Holocron = 7) attacks the base, deals 2 to
#// the friendly SOR_046, and gets +2 → deals 9 to the base.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_138
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:2
P2BASEDMG:9

---

# AttachRestriction_ForceUnitOnly
#// LOF_138 Sith Holocron — "Attach to a Force unit." Played from hand with TWO Force units (LOF_050 Plo Koon
#// at idx 0, SOR_038 Count Dooku at idx 2) and a non-Force unit (SOR_095 Battlefield Marine at idx 1), only
#// the two Force units are legal attach targets (two are used so the prompt is forced rather than a lone
#// target auto-resolving). Ref: "should only be attached to Force unit".

## GIVEN
CommonSetup: rrk/ggw/{myResources:2;handCardIds:LOF_138}
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_038:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-2

---

# DeclineDamage_NoBuff
#// LOF_138 Sith Holocron — the On-Attack ability is a "may". Plo Koon (6 + 1 from the +1/+1 Holocron = 7)
#// attacks the base; P1 DECLINES to deal 2 to a friendly unit, so no +2/+0 is granted and the base takes only
#// 7. The friendly SOR_046 takes no damage. Ref: scenario 2 "sith holocron bonus should be only for
#// the current attack — clickPrompt('Pass') → base damage 3 (2+1, no +2)".

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_138
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:0:BASE
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENAUNIT:1:DAMAGE:0
P2BASEDMG:7
