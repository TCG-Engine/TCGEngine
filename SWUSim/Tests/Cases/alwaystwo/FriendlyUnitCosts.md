#// ALWAYS TWO — ability COSTS that exhaust / defeat "a friendly unit" may use the TEAMMATE's units (owner ruling 2026-11-20, e.g.
#// Fennec Shand ASH). docs/superpowers/specs/2026-11-20-swusim-always-two-design.md ("Control, friendly, enemy, opponent").
#// Team Suns keeps these costs on your own board (core/TeamSuns_FriendlyPoolsSpanTheTeam.md::AbilityCostPaidWithAFriendlyUnit_StaysOwnBoard)
#// — the controls below prove it is unchanged. Board 1 fields SOR_095, teammate board 3 fields SOR_128; both ready.
#// Where the teammate's unit is the ONLY payment, the cost is auto-chosen, so those sections assert it was exhausted/defeated.
# TombOfEilram_CostMayExhaustTheTeammatesUnit
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBase:LOF_028}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>UseBaseAbility
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# TombOfEilram_UsableWithOnlyTheTeammatesUnit
#// Board 1 has no unit at all: the action is still available, and the teammate's unit pays.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myBase:LOF_028}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>UseBaseAbility
## EXPECT
# Tomb of Eilram always asks (a plain MZCHOOSE, not an auto-pick): offered, and its only choice is the teammate's unit.
P1HASDECISION
P1SELECTABLEEXACT:p3GroundArena-0

---

# FennecShand_CostMayExhaustTheTeammatesUnit
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myLeader:ASH_002}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# FennecShand_UsableWithOnlyTheTeammatesUnit
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myLeader:ASH_002}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 6
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P3GROUNDARENAUNIT:0:EXHAUSTED

---

# DirectorKrennic_CostMayDefeatTheTeammatesUnit
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myLeader:LAW_008}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# DirectorKrennic_UsableWithOnlyTheTeammatesUnit
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myLeader:LAW_008}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P3GROUNDARENACOUNT:0

---

# DarthVader_CostMayDefeatTheTeammatesUnit
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myLeader:IC27_001}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# EmperorPalpatine_CostMayDefeatTheTeammatesUnit
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myLeader:SOR_006}
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# ImpossibleEscape_CostMayExhaustTheTeammatesUnit
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 10
WithP1Hand: LOF_218
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_046:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:ExhaustFriendly
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&p3GroundArena-0

---

# Control_TeamSuns_FennecCostStaysOwnBoard
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk/{myLeader:ASH_002}
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Resources: 6
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
