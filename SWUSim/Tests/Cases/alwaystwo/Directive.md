#// ALWAYS TWO (Doubles) — the WithAlwaysTwo fixture directive. docs/superpowers/specs/2026-11-20-swusim-always-two-design.md
#// `WithAlwaysTwo: 1v1|4p` = four boards in two teams (1+3 Red, 2+4 Blue — WithTeams' seat order and SWU_MODE_TEAMS) plus the
#// Always Two flags. The rules themselves are pinned by the other files in this folder; this one only proves the directive builds a
#// four-board table.
# OneVsOne_BuildsAFourBoardTeamTable
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 1v1
WithActivePlayer: 1
## WHEN
## EXPECT
SEATCOUNT:4
PHASE:MAIN

---

# FourPlayer_BuildsAFourBoardTeamTable
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithAlwaysTwo: 4p
WithActivePlayer: 1
## WHEN
## EXPECT
SEATCOUNT:4
PHASE:MAIN
