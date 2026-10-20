#// TWIN SUNS HOUSE RULE — Extra Base HP (docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md).
#// Every base's max HP is printed HP + N. Base damage is a counter, so the bonus only matters where max HP is READ:
#// the defeat check, remaining HP (cards like You're My Only Hope, the bots) and the health pip (BasePrintedHP).
#// The canonical CommonSetup bases are 30 HP, so +10 makes them 40. P1's Blast deals 1 to each enemy base.
# Bonus_ABaseAt31DamageSurvives
#// 30 printed + 10 = 40. Without the rule 31 damage on a 30 HP base is defeat (see the control below).
## GIVEN
CommonSetup3P: bbk/bbk/bbk/{theirBaseDamage:30}
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: extraBaseHp=10
## WHEN
- P1>TakeCounter:blast
## EXPECT
SEATLIVE:2:true
P2BASEREMAINING:9
P2BASEMAXHP:40

---

# Bonus_ABaseAt40DamageIsDefeated
#// The raised max is still a max: 39 + 1 = 40 damage defeats the 40 HP base.
## GIVEN
CommonSetup3P: bbk/bbk/bbk/{theirBaseDamage:39}
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: extraBaseHp=10
## WHEN
- P1>TakeCounter:blast
## EXPECT
SEATLIVE:2:false

---

# NoRule_ABaseAt30DamageIsDefeated
#// The control: no house rule, printed 30 HP, 29 + 1 = 30 damage → defeated.
## GIVEN
CommonSetup3P: bbk/bbk/bbk/{theirBaseDamage:29}
SkipPreGame: true
WithActivePlayer: 1
## WHEN
- P1>TakeCounter:blast
## EXPECT
SEATLIVE:2:false
P1BASEMAXHP:30

---

# Bonus_FivePointStep_IsReadAsGiven
#// The value is whatever the room chose (here the maximum, 30): 30 + 30 = 60.
## GIVEN
CommonSetup3P: bbk/bbk/bbk/{theirBaseDamage:50}
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: extraBaseHp=30
## WHEN
## EXPECT
P2BASEREMAINING:10
P3BASEMAXHP:60

---

# TwoSeats_TheBonusNeverApplies
#// House rules are a Twin Suns (3-4 seat) feature. SWUBaseMaxHp returns printed HP at two seats WITHOUT reading the
#// house-rule variable, so a stray HR_EXTRABASEHP can never reach Premier — and Premier's hot paths (render, the bot
#// evaluator) pay nothing for the feature.
## GIVEN
CommonSetup: bbk/bbk/{theirBaseDamage:29}
SkipPreGame: true
WithActivePlayer: 1
WithHouseRules: extraBaseHp=10
## WHEN
## EXPECT
P1BASEMAXHP:30
P2BASEREMAINING:1
