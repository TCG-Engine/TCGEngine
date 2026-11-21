#// ALWAYS TWO — the Twin Suns rules that four boards would otherwise switch on stay OFF.
#// docs/superpowers/specs/2026-11-20-swusim-always-two-design.md ("Twin Suns rules that are OFF").
#// Always Two is the Doubles format over a Premier/Eternal game: the initiative is the only counter. No Blast, no Plan, no house-rule
#// 4th counter, and house rules never change base HP. (The no-strategic-pass rule IS kept — InitiativeAndPass.md.)
# BlastCounter_NotThere
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>TakeCounter:blast
## EXPECT
BLASTCOUNTER:AVAILABLE
P1BLASTAVAIL:0
P2BASEDMG:0
P4BASEDMG:0

---

# PlanCounter_NotThere
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>TakeCounter:plan
## EXPECT
PLANCOUNTER:AVAILABLE
P1PLANAVAIL:0

---

# HouseRules_NeverChangeBaseHp
#// Even if a game carried the extra-base-HP variable, Always Two reads the printed HP.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithHouseRules: extraBaseHp=10
## WHEN
## EXPECT
P2BASEMAXHP:30

---

# Control_TeamSuns_StillHasBlastAndPlan
#// Same four seats, Team Suns: the Blast is there and deals 1 to each ENEMY base.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
## WHEN
- P1>TakeCounter:blast
## EXPECT
BLASTCOUNTER:P1
P2BASEDMG:1
P4BASEDMG:1
