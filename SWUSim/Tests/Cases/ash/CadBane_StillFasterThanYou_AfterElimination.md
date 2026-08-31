# ASH_011 Cad Bane — Still Faster than You: the leader Action after a seat has been ELIMINATED.
#
# WHY THIS FILE EXISTS — game 4160, "after a player was eliminated, i can't choose a target with my
# Cad Bane leader."
#
# The reported defect was CLIENT-side (a Twin Suns game narrowed to two live seats stopped translating
# the server's seat-tagged `p{n}Zone-i` ids into the `their…` frame the 2-player board renders, so
# nothing was clickable). These sections guard the SERVER half of that seam, which was — and must stay
# — correct: ZoneSearch's seat-tagged fan-out is gated on SeatCountForGame(), which counts SEAT ORDER,
# so it keeps addressing the surviving opponent by seat for the rest of the game.
#
# ⚠ THIS IS THE GUARD AGAINST "FIXING" IT ON THE SERVER. The tempting alternative was to gate the
# fan-out on the LIVE seat count instead, so a narrowed game would emit plain `their…` and match the
# client with no client change. That is wrong and this file is what catches it: with two live seats out
# of an original three, an unqualified `their<Zone>` search resolved through the 2-player frame does
# NOT reach seat 3 — the frame's "other player" is seat 2, who is dead and holds nothing — so the pool
# would come back EMPTY and Cad Bane would fizzle without ever asking. That is a strictly worse bug
# than the one reported, and it is silent.
#
# Cad Bane's Action is "[Exhaust]: Deal 1 damage to a unit with 2 or more remaining HP", and its pool
# is every unit on the table, so with P1 controlling nothing the pool is entirely seat-tagged — which
# is exactly the shape that broke.

---

# OffersEliminatedGameSurvivorsUnits
#// SeatOrder 123 with seat 2 ELIMINATED. P1 controls no units, so every legal target belongs to seat 3
#// and the whole pool is seat-tagged. Two of seat 3's units qualify (>= 2 remaining HP) and two do not,
#// so the offer cannot auto-resolve and the prompt must appear — proving the pool is non-empty AND
#// that the >= 2 HP filter still discriminates across a seat boundary.
#// ⚠ The decision is deliberately LEFT PENDING: the tooltip is the evidence. If the fan-out regressed
#// to an empty pool the handler would call SWUAfterAction and there would be no decision to read.
## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:ASH_011
}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 13
WithActivePlayer: 1
WithP1Resources: 2
#// Seat 3's board: SOR_046 (3/7, 4 dmg -> 3 left) and SOR_095 (3/3, 0 dmg -> 3 left) both qualify;
#// SOR_108 (1/2, 1 dmg -> 1 left) and SOR_128 (3/1, 0 dmg -> 1 left) do not.
WithP3GroundArena: SOR_046:1:4
WithP3GroundArena: SOR_108:1:1
WithP3GroundArena: SOR_128:1:0
WithP3GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1DECISIONTOOLTIP:Deal_1_to_a_unit_with_2+_remaining_HP
P1LEADER:EXHAUSTED

---

# DamageLandsOnTheSurvivingSeat
#// The same board, now answered: the damage must reach seat 3's unit. A pool that addressed the wrong
#// frame would either fizzle or hit nothing, and P3GROUNDARENAUNIT is what proves it landed on the
#// surviving OPPONENT rather than somewhere in the caster's own frame.
## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:ASH_011
}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 13
WithActivePlayer: 1
WithP1Resources: 2
WithP3GroundArena: SOR_046:1:4
WithP3GroundArena: SOR_095:1:0
## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:p3GroundArena-0
## EXPECT
P3GROUNDARENAUNIT:0:DAMAGE:5
P3GROUNDARENAUNIT:1:DAMAGE:0
P1LEADER:EXHAUSTED

---

# NoQualifyingTargetAnywhere_Fizzles
#// The NEGATIVE control. Seat 3's only units are both at 1 remaining HP, so nothing qualifies and the
#// ability must NOT prompt — it just consumes the action. Without this, the first section could pass
#// on a pool that offered every unit regardless of the >= 2 HP filter.
## GIVEN
CommonSetup: rrk/bbw/{
  myLeader:ASH_011
}
SkipPreGame: true
WithSeatOrder: 123
WithLiveSeats: 13
WithActivePlayer: 1
WithP1Resources: 2
WithP3GroundArena: SOR_108:1:1
WithP3GroundArena: SOR_128:1:0
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1NODECISION
P1LEADER:EXHAUSTED
P3GROUNDARENAUNIT:0:DAMAGE:1
P3GROUNDARENAUNIT:1:DAMAGE:0
