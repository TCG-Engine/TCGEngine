# AdiGallia_OnSeat3_P1PlaysAnEvent
#// Player report (Twin Suns, 2026-09-21): "Adi Gallia did not properly deal damage to the opponent's base when they
#// played events." LOF_142 Adi Gallia — "When an opponent plays an event: deal 1 damage to that player's base."
#// The event-play block (ActivateCard) looked for Adi on OtherPlayer($player) — a TWO-SEAT helper (1→2, every other
#// seat→1) — so at three or four seats most Adis were invisible. Seat 3's Adi, P1 plays Confiscate (SOR_251):
#// OtherPlayer(1) = 2 checked seat 2 only.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 2
WithP1Hand: SOR_251
WithP3GroundArena: LOF_142:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:1
P2BASEDMG:0
P3BASEDMG:0

---

# AdiGallia_OnSeat2_P3PlaysAnEvent
#// The other half of the two-seat read: OtherPlayer(3) = 1, so seat 2's Adi never reacted to seat 3.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 3
WithP3Resources: 2
WithP3Hand: SOR_251
WithP2GroundArena: LOF_142:1:0
## WHEN
- P3>PlayHand:0
## EXPECT
P3BASEDMG:1

---

# AdiGallia_OnTwoOpponents_EachDeals1
#// "When an opponent plays an event" is an observer on EVERY opponent's board: two opponents' Adis, 2 damage.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 2
WithP1Hand: SOR_251
WithP2GroundArena: LOF_142:1:0
WithP3GroundArena: LOF_142:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:2

---

# AdiGallia_TeamSuns_TeammateDoesNotReact_EnemyDoes
#// Team Suns: a teammate is never an opponent (OpponentsOf is team-aware). Seat 1's teammate is seat 3; seat 2 is an
#// enemy. Only the enemy's Adi deals damage.
## GIVEN
CommonSetup4P: bbk/bbk/bbk/bbk
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithP1Resources: 2
WithP1Hand: SOR_251
WithP2GroundArena: LOF_142:1:0
WithP3GroundArena: LOF_142:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:1

---

# SawGerrera_OnSeat3_TaxesP1sEvent
#// SOR_153 Saw Gerrera — "As an additional cost for each opponent to play an event, they must deal 2 damage to their
#// base." Same block, same two-seat read.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 2
WithP1Hand: SOR_251
WithP3GroundArena: SOR_153:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
P1BASEDMG:2

---

# Relentless_OnSeat3_BlanksP1sFirstEvent
#// SOR_089 Relentless — "The first event played by each opponent each round loses all abilities." Same block.
## GIVEN
CommonSetup3P: bbk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 1
WithP1Resources: 2
WithP1Hand: SOR_251
WithP3SpaceArena: SOR_089:1:0
## WHEN
- P1>PlayHand:0
## EXPECT
LOGCOUNT:1:loses all abilities (Relentless)
