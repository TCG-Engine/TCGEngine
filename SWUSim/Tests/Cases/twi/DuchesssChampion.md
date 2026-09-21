# Sentinel_WhileOpponentThreeUnits
#// TWI_054 Duchess's Champion (Unit 1/8, Ground) — "While an opponent controls 3 or more units, this
#// unit gains Sentinel." Guard: with 3 enemy units in play, TWI_054 reports HASKEYWORD Sentinel.

## GIVEN
CommonSetup: bbk/grw/{myResources:0}
P1OnlyActions: true
WithP1GroundArena: TWI_054:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SEC_080:1:0

## WHEN
- P1>Pass

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# TwinSuns_ANYOpponentWithThreeUnits_NotJustTheNextSeat
#// BUG (game 850132). "An opponent" means ANY opponent. The section above cannot see the defect: with
#// only two seats there is exactly one opponent, so a read that checks a SINGLE seat is accidentally
#// right. Here the threshold is met by the FAR seat only — P2 holds 2 units (below it) and P3 holds 3
#// (at it) — so a one-seat read answers "no Sentinel" and a correct read answers "Sentinel".
#// Live: P1's Duchess's Champion never gained Sentinel while P3 sat on 4 units and P2 on 2.
#// KeywordEffects.php read OtherPlayer($ctrl), which is a TWO-SEAT helper: it answers 2 for seat 1 and
#// 1 for every other seat, so the third seat is invisible to it.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: TWI_054:1:0
WithP2GroundArena: [SEC_080:1:0 SEC_080:1:0]
WithP3GroundArena: [SEC_080:1:0 SEC_080:1:0 SEC_080:1:0]

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_054
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# TwinSuns_NoOpponentReachesThree_NoSentinel
#// The negative that keeps the section above honest: if it passed by granting Sentinel unconditionally
#// this one reds. Both opponents sit at 2 units — under the threshold on every seat — so no Sentinel.

## GIVEN
CommonSetup3P: bbk/grw/grw
SkipPreGame: true
WithActivePlayer: 1
WithP1GroundArena: TWI_054:1:0
WithP2GroundArena: [SEC_080:1:0 SEC_080:1:0]
WithP3GroundArena: [SEC_080:1:0 SEC_080:1:0]

## WHEN
- P1>Pass

## EXPECT
SEATCOUNT:3
P1GROUNDARENAUNIT:0:CARDID:TWI_054
P1GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
