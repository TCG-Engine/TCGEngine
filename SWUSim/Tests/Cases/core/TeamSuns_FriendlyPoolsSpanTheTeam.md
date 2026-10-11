# FriendlyPool_IncludesTheTeammatesUnit
#// ─── "FRIENDLY" spans the TEAM — but "attack with" does NOT ─────────────────────────────────────
#//
#// User ruling 2026-08-25 (IBH_095 "Defeat a friendly unit" — a teammate's counts): in Team Suns a
#// teammate's unit IS friendly. The engine agrees — `team<Zone>` is documented as THE friendly pool,
#// "the caller's own zone plus each live teammate's". So a card reading "a friendly unit" wants
#// SWUAllUnits('team'); many were scoped to 'my' and could not see a teammate's board at all.
#//
#// ⚠ THE CR DOES NOT DEFINE THIS. CR 3.3.a says "a card that a player controls is considered friendly
#// for that player", and the CR has no teammate concept at all (§12 Twin Suns is free-for-all). Team
#// Suns is a house format, so the ruling above is what governs — not a CR citation.
#//
#// ⚠ AND "FRIENDLY" IS NOT THE ONLY THING A 'my' POOL CAN MEAN. That is what this pair exists to pin,
#// because a sweep keyed on the word "friendly" gets it wrong: three cards in this family were
#// misclassified on the first pass. Two shapes must keep a 'my' pool even though their text says
#// "friendly":
#//   • a CONTROLLER-ONLY VERB — "attack with a friendly unit" (LAW_065, HMW_266). You cannot attack
#//     with a teammate's unit, so widening the pool offers an illegal choice. HMW_266 was caught only
#//     because it happened to have a Team Suns section; LAW_065 had none and would have shipped.
#//   • "a unit YOU CONTROL" / "your unit" — control is per-player, so a teammate's unit is not yours.
#// The third miss was the reverse: JTL_145 BB-8's only "friendly" is in its PILOTING REMINDER (where
#// it attaches), while the clause being pooled is an unqualified "ready a Resistance unit" — whole
#// table, not 'team'.
#//
#// ⚠ THE POOL IS THE ASSERTION. These cards behave identically on your own board either way, so a
#// test that plays the card and checks a result passes regardless. Leave the decision PENDING and pin
#// the exact selectable set.
#//
#// ── SEC_091 Corporate Warmongering — "Give a friendly unit +3/+3 for this phase. Give each OTHER
#// friendly unit +1/+1 for this phase."
#//
#// ⚠ ASSERT THE BUFF, NOT THE OFFER. The +3/+3 OFFER already used SWUFriendlyUnits() and was always
#// team-correct; the pool that was scoped to 'my' is the "each other friendly unit" +1/+1 LOOP, which
#// raises no prompt at all. A first cut of this section pinned the offer instead and was DECORATIVE —
#// reverting the fix left it green. Mutation caught that; nothing else would have.
#//
#// P1 takes the +3/+3 (3/3 -> 6/6). Teammate p3 is "another friendly unit" and takes +1/+1 (3/1 -> 4/2)
#// — that is the assertion. Enemy p2 is untouched at 3/7, which also rules out a loop widened to the
#// whole table.

## GIVEN
CommonSetup: rrk/bbw/{myResources:8}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: SEC_091
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
SEATCOUNT:4
# The chosen unit takes +3/+3.
P1GROUNDARENAUNIT:0:POWER:6
P1GROUNDARENAUNIT:0:HP:6
# The TEAMMATE is "another friendly unit" and takes +1/+1 — the whole point of the fix.
P3GROUNDARENAUNIT:0:POWER:4
P3GROUNDARENAUNIT:0:HP:2
# The enemy is untouched.
P2GROUNDARENAUNIT:0:POWER:3
P2GROUNDARENAUNIT:0:HP:7

---

# AttackWithAFriendlyUnit_StaysControllerOnly
#// ⚠ THE COUNTEREXAMPLE, and the reason this family cannot be swept on the word "friendly".
#// LAW_065 4-LOM — "When Played: You may attack with a friendly Bounty Hunter unit, even if it's
#// exhausted." Attacking is an action only a unit's CONTROLLER may take, so this pool must stay 'my'
#// however friendly a teammate is. Widening it would offer P1 a unit it cannot legally attack with.
#//
#// Teammate p3 fields a Bounty Hunter of its own (SOR_204 Greedo, identical to P1's), so 'my' and
#// 'team' give visibly different pools: three entries vs four. Converting this card reds here and
#// nowhere else. HMW_266 Familiar Strategem ("attack with a unit") is the same shape and was caught
#// only because it happened to already have a Team Suns section — this one had none.
#//
#// The pool is all three of P1's Bounty Hunters: Greedo, Boba Fett, and 4-LOM HIMSELF (he has the
#// trait and "even if it's exhausted" covers his just-entered state).
#//
#// ⚠ FIXTURE: 4-LOM carries THREE aspects, so the penalty can reach +6 on top of his cost of 5 —
#// 14 resources, or he silently never leaves hand and the section reads as a rules failure. He also
#// only offers attackers that have a legal target ("can't attack bases for this attack"), which is
#// why P2 must field a ground unit.

## GIVEN
CommonSetup: rrk/bbw/{myResources:14}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1Hand: LAW_065
WithP1GroundArena: SOR_204:1:0
WithP1GroundArena: SOR_179:1:0
WithP2GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_204:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1HASDECISION
P1DECISIONTOOLTIP:Choose_a_Bounty_Hunter
# P1's three Bounty Hunters only — the teammate's is friendly but cannot be attacked with.
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1&myGroundArena-2

---

# AbilityCostPaidWithAFriendlyUnit_StaysOwnBoard
#// ⚠ A JUDGEMENT CALL, PINNED HERE SO IT IS VISIBLE AND EASY TO REVERSE.
#//
#// LOF_028 Tomb of Eilram — "Action [exhaust a friendly unit]: The Force is with you." The card says
#// "friendly", which by the 2026-08-25 ruling spans the team — but this is an ability COST, not an
#// effect, and a cost is paid from YOUR OWN board. Letting it exhaust a teammate's unit would spend an
#// ally's card without their agreement, which is a larger step than an effect merely REACHING a
#// teammate. So cost pools stay SWUControlledUnits() while effect pools move to SWUFriendlyUnits().
#//
#// The same call was made on IC27_001 Darth Vader and SOR_006 Emperor Palpatine, which each have BOTH
#// shapes: their On Attack "defeat another friendly unit" EFFECT spans the team, their Action's
#// "[defeat a friendly unit]" COST does not. LOF_218 Impossible Escape is the fourth.
#//
#// ⚠ IF THE OWNER RULES THE OTHER WAY, this section is the single place that says so — change it and
#// the four cost pools together. Nothing else in the suite encodes this distinction.
#//
#// P1 and teammate p3 each field a unit; only P1's may be exhausted to pay.

## GIVEN
CommonSetup: rrk/bbw/{myBase:LOF_028}
SkipPreGame: true
P1OnlyActions: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP1GroundArena: SOR_095:1:0
WithP1GroundArena: SOR_046:1:0
WithP3GroundArena: SOR_128:1:0

## WHEN
- P1>UseBaseAbility

## EXPECT
SEATCOUNT:4
P1HASDECISION
# Only P1's own two units — the teammate's is friendly but cannot pay P1's cost.
P1SELECTABLEEXACT:myGroundArena-0&myGroundArena-1
