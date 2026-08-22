# WhenPlayed_OpponentCreates2Droids
#// TWI_145 Jesse (Unit 4/4, Ground, cost 3, Aggression/Heroism) — "Raid 1. When Played: An opponent
#// creates 2 Battle Droid tokens." Jesse enters P1's ground; her When Played makes the OPPONENT create
#// 2 Battle Droid (TWI_T01) tokens on P2's side. (Raid 1 keyword is covered generically.)
#// Base r = Aggression + leader rw = Aggression/Heroism cover both pips → no penalty.

## GIVEN
CommonSetup: rrw/grw/{myResources:3;handCardIds:TWI_145}
P1OnlyActions: true

## WHEN
- P1>PlayHand:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:TWI_145
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:TWI_T01

---

# TwinSuns_TheDroidsGoToTheCHOSENOpponent
#// ⚠ THE SEAT-COUNT CELL — added 2026-08-23 (Pass 1, PROMPT). "An opponent creates 2 Battle Droid tokens"
#// is a DRAWBACK being aimed — Jesse hands two bodies to someone — so which opponent receives them is a
#// real decision, and OtherPlayer() made it silently.
#// ⚠ NO $eligible filter: token creation always succeeds; no board, hand, deck or capacity state can make
#// a live opponent unable to receive them, so nobody can be filtered out as unaffected.
#// ⚠ Note the intuition trap: because this clause HELPS the chosen player, it is tempting to reason about
#//   "who benefits" when deciding eligibility. Eligibility is about whether the effect can HAPPEN, never
#//   about who it favours — the taxonomy in the sweep plan turns on WHO ACTS, not on who gains.
#// P1 picks SEAT 3, who must get exactly 2 Battle Droids; seats 2 and 4 must get none.
#// ⚠ A 2-player version CANNOT FAIL — one opponent means no choice to get wrong.
#// Mutation check: revert to OtherPlayer() and this reds.

## GIVEN
CommonSetup: rrw/grw/{myResources:3;handCardIds:TWI_145}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithActivePlayer: 1
WithGamePhase: ActionPhase
P1OnlyActions: true
WithP3Base: SOR_021:0
WithP4Base: SOR_021:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P3

## EXPECT
SEATCOUNT:4
P3GROUNDARENACOUNT:2
P3GROUNDARENAUNIT:0:CARDID:TWI_T01
P2GROUNDARENACOUNT:0
P4GROUNDARENACOUNT:0
