# DebuffsEnemyNonLeaderUnits
#// SHD_037 Supreme Leader Snoke — passive field-presence debuff:
#//   "Each enemy non-leader unit gets –2/–2."
#// P1 plays Snoke. P2's AT-AT (9/9) is an enemy unit → 7/7. P1's own Imperial
#// Dark Trooper (3/3) is friendly to Snoke's controller → unaffected, stays 3/3.

## GIVEN
CommonSetup: bbk/bbk/{myResources:8;handCardIds:SHD_037}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_088:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HP:3
P1GROUNDARENAUNIT:1:CARDID:SHD_037
P2GROUNDARENAUNIT:0:CARDID:SOR_088
P2GROUNDARENAUNIT:0:POWER:7
P2GROUNDARENAUNIT:0:HP:7

---

# DefeatsLowHpEnemyUnit
#// SHD_037 Supreme Leader Snoke — the passive –2/–2 lowers HP directly (not damage),
#// so an enemy non-leader unit whose HP drops to 0 is defeated as a state-based effect.
#// P1 plays Snoke while P2 controls Leia (SOR_189, 2/2) → 2/2 becomes 0/0 → defeated.

## GIVEN
CommonSetup: bbk/bbk/{myResources:8;handCardIds:SHD_037}
WithP2GroundArena: SOR_189:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SHD_037
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1

---

# EnemyDebuff
#// SHD_037 Supreme Leader Snoke (8-cost ground) — "Each enemy non-leader unit gets -2/-2." Guard: the enemy
#// SOR_046 (3/7) is reduced to 1/5 while Snoke is in play.

## GIVEN
CommonSetup: bbk/bbk
P1OnlyActions: true
WithP1GroundArena: SHD_037:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>Pass

## EXPECT
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:POWER:1
P2GROUNDARENAUNIT:0:HP:5

---

# TwinSuns_ASnokeOnANYSeatShrinksYou
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-21 during the "an opponent" sweep. "Each ENEMY non-leader unit
#// gets -2/-2" is relative to Snoke's controller, so a Snoke on ANY opposing seat shrinks you.
#// SWUEnemySnokeCount resolved the shrinking side with GetOpponent($controller) — one seat, and
#// `return null` for anything above seat 2. So a SEAT-3 Snoke shrank nobody at all, and a seat-3 unit was
#// never shrunk by anyone. (GetOpponent is a THIRD legacy helper alongside OtherPlayer/SWUChooseOpponent,
#// and the worst of them: it hands back NULL rather than a wrong seat, so the check silently answers
#// "no".)
#// Seat 3 controls the only Snoke. P1's Battlefield Marine (3/3) must read 1/1.
#// ⚠ A 2-player version cannot fail — with two seats GetOpponent was already right. The seat count IS
#//   the test, and the Snoke must sit on a FAR seat (3 or 4), not on seat 2.

## GIVEN
CommonSetup: bbw/rrk/{}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP3GroundArena: SHD_037:1:0
WithP1Deck: [SOR_095 SOR_046 SEC_080]

## WHEN

## EXPECT
SEATCOUNT:4
P1GROUNDARENAUNIT:0:POWER:1
P1GROUNDARENAUNIT:0:HP:1
P3GROUNDARENAUNIT:0:CARDID:SHD_037
P3GROUNDARENAUNIT:0:POWER:6
