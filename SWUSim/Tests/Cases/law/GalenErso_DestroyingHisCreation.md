# EnemyUnitsGainRaidSaboteur
#// LAW_233 Galen Erso (Cunning, cost 3) — When Played: you may have an opponent take control (declined
#// here). Passive: "Enemy units gain Raid 1 and Saboteur." The enemy SOR_046 gains both.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP1Hand: LAW_233

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:HASKEYWORD:Raid
P2GROUNDARENAUNIT:0:HASKEYWORD:Saboteur

---

# GiveControlToOpponent
#// LAW_233 Galen Erso — When Played you MAY have the opponent take control. Accept: Galen leaves P1's arena
#// and is now controlled by P2.

## GIVEN
CommonSetup: yyk/bgw/{myResources:3}
P1OnlyActions: true
WithP1Hand: LAW_233

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:YES

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENAUNIT:0:CARDID:LAW_233

---

# EnemyRaidAddsDamage
#// LAW_233 Galen Erso — the granted Raid 1 is a real power buff. With Galen on P1's board, P2's units are
#// "enemy" and gain Raid 1: an enemy Battlefield Marine (3/3) attacks P1's base for 3 + 1 = 4.

## GIVEN
CommonSetup: yyk/bgw/{}
WithActivePlayer: 2
WithInitiativePlayer: 2
WithInitiativeClaimed: true
WithP1GroundArena: LAW_233:1:0
WithP2GroundArena: SOR_095:1:0

## WHEN
- P2>AttackGroundArena:0:BASE

## EXPECT
P1BASEDMG:4

---

# FriendlyUnitsNoRaid
#// LAW_233 Galen Erso — only ENEMY units gain Raid 1. A friendly Battlefield Marine (3/3) attacks the enemy
#// base for 3 (no Raid bonus).

## GIVEN
CommonSetup: yyk/bgw/{}
P1OnlyActions: true
WithP1GroundArena: LAW_233:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>AttackGroundArena:1:BASE

## EXPECT
P2BASEDMG:3

---

# TwinSuns_AGalenOnANYSeatGrantsToEveryone
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 during the "an opponent" sweep (Pass 0 seam 9).
#// "Enemy units gain Raid 1 and Saboteur" is relative to GALEN's controller, so a Galen on ANY opposing
#// seat grants to you. Both halves of the aura resolved the enemy side with
#// `_SWUCountUnitsWithCardID(OtherPlayer($obj->Controller), 'LAW_233')` — ONE seat. So above two seats a
#// Galen on seat 3 or 4 granted NOTHING TO ANYBODY, and the bug is invisible in Premier where
#// OtherPlayer() is the right answer. Now `_SWUAnyOpponentControlsActive()`.
#// The aura is also ability-active, not merely controlled: a BLANKED Galen projects nothing (same rule
#// as SWUCreditAbilitiesDisabled/LAW_117), which is why the helper counts via
#// _SWUCountActiveUnitsWithCardID.
#// Seat 3 controls the only Galen. P1's Battlefield Marine must gain BOTH keywords.
#// ⚠ A 2-player version CANNOT FAIL — at two seats OtherPlayer() already named the Galen's seat. The
#//   seat count IS the test, and the Galen must sit on a FAR seat (3 or 4), never on seat 2.
#// Mutation check: revert either site to _SWUCountUnitsWithCardID(OtherPlayer(...)) and this reds while
#// every existing 2-player section above stays green. That asymmetry is the proof.

## GIVEN
CommonSetup: bbw/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: LAW_233:1:0

## WHEN

## EXPECT
SEATCOUNT:4
P3GROUNDARENAUNIT:0:CARDID:LAW_233
P1GROUNDARENAUNIT:0:CARDID:SOR_046
P1GROUNDARENAUNIT:0:HASKEYWORD:Raid
P1GROUNDARENAUNIT:0:HASKEYWORD:Saboteur
