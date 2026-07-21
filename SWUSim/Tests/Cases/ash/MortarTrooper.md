# DealOneToThreeGround
#// ASH_142 Mortar Trooper (Ground, 1/4) — Action [Exhaust]: deal 1 damage to each of up to 3 ground units.
#// P1 picks three enemy ground units (SEC_080, SOR_046, SOR_095); each takes 1.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_142:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:1

---

# DealToSingleUnit
#// ASH_142 Mortar Trooper — "up to 3" may be just one. P1 deals 1 to only SEC_080; the others are untouched.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_142:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:0
