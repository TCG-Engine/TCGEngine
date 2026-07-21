# GrantOverwhelm
#// LOF_114 Kaadu — When Played: may give another friendly unit Overwhelm for this phase. P1 grants
#// Overwhelm to its SOR_095.

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:LOF_114}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Overwhelm

---

# GrantOverwhelm_SpaceUnit_ExcessToBase
#// LOF_114 Kaadu — the "another friendly unit" it grants Overwhelm to may be a SPACE unit, and the granted
#// Overwhelm actually spills excess combat damage to the base. P1's space Cartel Spacer (SOR_178, 2/3)
#// receives Overwhelm from Kaadu, then attacks the enemy 1/1 Patrolling V-Wing (TWI_107): it defeats the
#// V-Wing and the 1 excess (2 power − 1 HP) carries over to P2's base. Ref: "should give friendly
#// space unit Overwhelm."

## GIVEN
CommonSetup: ggw/rrk/{myResources:4;handCardIds:LOF_114}
P1OnlyActions: true
WithP1SpaceArena: SOR_178:1:0
WithP2SpaceArena: TWI_107:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AttackSpaceArena:0:theirSpaceArena-0

## EXPECT
P1SPACEARENAUNIT:0:HASKEYWORD:Overwhelm
P2SPACEARENACOUNT:0
P2BASEDMG:1
