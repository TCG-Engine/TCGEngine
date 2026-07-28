# DealPowerToGround
#// ASH_123 Lang (Ground, 2/5) — Action [Exhaust]: this unit deals damage equal to his power to a ground
#// unit. Lang (power 2) uses his action and deals 2 to the enemy SEC_080.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_123:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_123
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# Action_DealsPowerToGroundUnit
#// ASH_123 Lang — Action [Exhaust]: deals damage equal to his power (2) to a ground unit. Lang deals 2 to
#// the enemy SOR_046.
## GIVEN
CommonSetup: rrk/rrk
WithP1GroundArena: ASH_123:1:0
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# DealsPowerToHimself
#// ASH_123 Lang — his action can target himself. Lang (power 2) deals 2 damage to himself and exhausts.
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_123:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:ASH_123
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# DamageScalesWithBuffedPower
#// ASH_123 Lang — the damage equals his CURRENT power. With Academy Training (SOR_120, +2/+2) Lang is at
#// power 4, so his action deals 4 to the enemy SOR_046 (7 HP, survives).
## GIVEN
CommonSetup: ggk/ggk
WithP1GroundArena: ASH_123:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2GroundArena: SOR_046:1:0
P1OnlyActions: true
## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:4
