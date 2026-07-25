# StatBonus
#// LOF_040 Kylo Ren's Lightsaber (+1/+3) — Attach to a non-Vehicle unit. On the Force unit Plo Koon (6/8)
#// it makes him 7/11. (The "can't be exhausted by enemy abilities" grant is wired via SWUAvoidsExhaust.)

## GIVEN
CommonSetup: rrk/ggw
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_040

## EXPECT
P1GROUNDARENAUNIT:0:POWER:7
P1GROUNDARENAUNIT:0:HP:11

---

# ForceUnit_CannotBeExhaustedByEnemyAbility
#// LOF_040 Kylo Ren's Lightsaber — "If attached unit is a Force unit, it gains: This unit can't be
#// exhausted by enemy card abilities." Plo Koon (LOF_050, Force,Jedi,Republic) bears the saber. P2 plays No
#// Good to Me Dead (SOR_186, "Exhaust a unit") targeting Plo Koon — the enemy exhaust is prevented and Plo
#// stays ready. Ref: "if attached unit is a Force unit, it cannot be exhausted by enemy card
#// abilities."

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: LOF_050:1:0
WithP1GroundArenaUpgrade: 0:LOF_040
WithP2Hand: SOR_186
WithP2Resources: 6

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:LOF_050
P1GROUNDARENAUNIT:0:READY

---

# NonForceUnit_CanBeExhaustedByEnemyAbility
#// LOF_040 Kylo Ren's Lightsaber — the exhaust immunity is conditional on the attached unit being a Force
#// unit. Attached to the non-Force Hylobon Enforcer (SHD_027, Underworld), the grant does NOT apply, so No
#// Good to Me Dead (SOR_186) exhausts it normally. Ref: "if attached unit is not a Force unit, it
#// can be exhausted by enemy card abilities."

## GIVEN
CommonSetup: bbk/yyw/{myBase:SOR_021;theirBase:SOR_021}
SkipPreGame: true
WithActivePlayer: 2
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SHD_027:1:0
WithP1GroundArenaUpgrade: 0:LOF_040
WithP2Hand: SOR_186
WithP2Resources: 6

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_027
P1GROUNDARENAUNIT:0:EXHAUSTED
