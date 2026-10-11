# BonusAndSaboteurAfterEnemyDefeated
#// TWI_143 Jyn Erso (Unit 3/2, Ground) — "While an enemy unit has been defeated this phase, this unit
#// gets +1/+0 and gains Saboteur." SOR_046 attacks and defeats the enemy SOR_128 (3/1) → an enemy was
#// defeated this phase → Jyn is now 4/2 with Saboteur.

## GIVEN
CommonSetup: rrw/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_143:1:0
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# NoDefeat_NoBonus
#// CONTROL for every section in this file. Nothing has been defeated this phase, so Jyn is her printed
#// 3/2 with no Saboteur. Without this, a condition that always answered "yes" would pass every positive
#// section here and the whole file would be vacuous.

## GIVEN
CommonSetup: rrw/grw
WithP1GroundArena: TWI_143:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN

## EXPECT
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:NOTKEYWORD:Saboteur

---

# EnemyDefeatedItsOwnUnit_StillCounts
#// ⚠ "While AN ENEMY UNIT has been defeated this phase" is EXISTENTIAL — it does NOT say "while you
#// have defeated an enemy unit" (contrast SHD_182 Bravado, which does). Official rulings for this
#// family read the same way (Iden Versio SOR_002, Brutal Traditions SHD_038: "even if no enemy unit
#// left play this phase"), and the "multiple opponents → you choose one" clause applies to cards that
#// say "an opponent", which this does not.
#//
#// Here P2 defeats their OWN unit: TWI_182 Infiltrating Demolisher's Exploit 1 eats a Battle Droid
#// token while being played. No combat, no P1 involvement. An enemy unit was still defeated, so Jyn
#// gets +1/+0 and Saboteur.
#//
#// ⚠ WHY THIS WAS INVISIBLE: SWU_ENEMY_DEFEATED is stamped under `if ($owner !== intval($player))`
#// (CombatLogic 811/848), so a player defeating their own unit stamps it on NOBODY. SWU_FRIENDLY_DEFEATED
#// is stamped unconditionally on the controller, which is why that is the flag to read across opponents.
#// This half of the bug bites at TWO seats, not just in Twin Suns.

## GIVEN
CommonSetup: rrw/yyk
WithActivePlayer: 2
WithP1GroundArena: TWI_143:1:0
WithP2GroundArena: TWI_T01:1:0
WithP2Hand: TWI_182
WithP2Resources: 6

## WHEN
- P2>PlayHand:0
- P2>AnswerDecision:myGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# ThreeSeat_DefeatBetweenTwoOtherSeats_Counts
#// The multi-seat half. P2 attacks and defeats P3's unit; P1 takes no part. Both are enemies of P1, so
#// from P1's seat "an enemy unit has been defeated this phase" is true.
#//
#// ⚠ SWU_ENEMY_DEFEATED is stamped on the DEFEATING seat only (CombatLogic 3545), so P1 never sees a
#// defeat it wasn't party to. TWO SEATS CANNOT OBSERVE THIS — there the defeater is the only other
#// seat, so its own flag is always the right answer.

## GIVEN
CommonSetup3P: rrw/rrk/bbk
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: TWI_143:1:0
WithP2GroundArena: SOR_039:1:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P2>AttackGroundArena:0:P3G0

## EXPECT
P3GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# FourSeat_DefeatBetweenTwoOtherSeats_Counts
#// 4P sibling: seat 2 defeats SEAT 4's unit, with seat 3 an uninvolved bystander. Both are P1's enemies,
#// so Jyn still gets +1/+0 and Saboteur.

## GIVEN
CommonSetup4P: rrw/rrk/bbk/bbk
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: TWI_143:1:0
WithP2GroundArena: SOR_039:1:0
WithP4GroundArena: SOR_128:1:0

## WHEN
- P2>AttackGroundArena:0:P4G0

## EXPECT
SEATCOUNT:4
P4GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:POWER:4
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
