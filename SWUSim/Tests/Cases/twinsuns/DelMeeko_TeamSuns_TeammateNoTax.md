# DelMeeko_TeamSuns_TeammatesEventCostsNormal
#// SOR_034 Del Meeko — "Each event an opponent plays costs 1 resource more." Its cost modifier treated ANY other
#// controller as an opponent (`srcController !== subjectPlayer`), so in Team Suns a TEAMMATE's events also cost +1
#// (found 2026-09-21 while fixing the far-seat event observers). A teammate is never an opponent (SWUIsEnemySeat).
#// Seat 1 has exactly 1 resource; Confiscate (SOR_251) costs 1. With Del Meeko on seat 3 — seat 1's teammate —
#// the event must still be playable.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP1Resources: 1
WithP1Hand: SOR_251
WithP3GroundArena: SOR_034:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:0

---

# DelMeeko_TeamSuns_EnemysEventCostsOneMore
#// The enemy half: Del Meeko on seat 2 (an enemy of seat 1) — Confiscate now costs 2, so 1 resource cannot play it.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP1Resources: 1
WithP1Hand: SOR_251
WithP2GroundArena: SOR_034:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:1

---

# DelMeeko_TwinSuns_FreeForAll_EveryOtherSeatIsAnOpponent
#// Control: Twin Suns free-for-all (no teams) — seat 3 IS an opponent, so its Del Meeko taxes seat 1's event.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 1
WithP1Hand: SOR_251
WithP3GroundArena: SOR_034:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1HANDCOUNT:1
