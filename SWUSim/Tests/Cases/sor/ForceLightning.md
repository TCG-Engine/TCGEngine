# LosesAbilities_ForceUnitDamage
#// SOR_138 Force Lightning (Event, cost 1, Aggression/Villainy) — "Choose a unit. It loses all abilities
#// for this phase. Then, if you control a FORCE unit, pay any number of resources and deal 2 damage to
#// the chosen unit for each resource paid this way." P1 controls a Force unit (SOR_051 Luke), targets the
#// enemy SOR_063 (Sentinel, 2/4): it loses Sentinel and, paying 1 resource, takes 2 damage (survives).
#// Spend = 1 (card) + 1 (X) of 3 ready → 1 left.
#// COVERAGE: offer=OfferPool_AllUnitsBothSides (friendly + enemy, both arenas, asserted pending) ·
#//           reqboundary=AuraBlank_ExpiresNextPhase (the blank marker and damage survive the
#//           serialized regroup crossing; expiry is re-evaluated from stored state) · control=N/A
#//           (the blank is a per-unit TurnEffect on the target itself, not seat-bound; the Force-unit
#//           gate is re-read live at resolution) · boundary pair=PayZero_BlankButNoDamage vs
#//           LosesAbilities_ForceUnitDamage (X=0 vs X=1) + PayMax_DefeatsTarget (X=cap) ·
#//           decline=PayZero_BlankButNoDamage (the pay step's zero option; both halves of the card
#//           are otherwise mandatory)

## GIVEN
CommonSetup: rrk/rrk/{myResources:3}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2GroundArena: SOR_063:1:0
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:1

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:1

---

# NoForceUnit_AbilitiesOnly
#// SOR_138 Force Lightning — the "loses all abilities" half is unconditional, but the "pay resources,
#// deal 2 each" half is gated on controlling a FORCE unit. With no Force unit, the enemy SOR_063 loses
#// Sentinel but takes NO damage and there is no pay step.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP2GroundArena: SOR_063:1:0
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:NOTKEYWORD:Sentinel
P2GROUNDARENAUNIT:0:DAMAGE:0
P1RESAVAILABLE:0

---

# AuraBlank_SilencesConstantAbility
#// SOR_138 Force Lightning — "loses all abilities" also silences a CONSTANT aura: blanking the enemy
#// JTL_085 Victor Leader ("each other friendly space unit gets +1/+1") drops its TIE Bomber wingman
#// (JTL_237, printed 0/4) back to 0 power / 4 HP. X=1 also puts 2 damage on Victor Leader itself.
#// Spend = 1 (card) + 1 (X) of 10 → 8 left.

## GIVEN
CommonSetup: rrk/rrk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2SpaceArena: [JTL_085:1:0 JTL_237:1:0]
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:1

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:2
P2SPACEARENAUNIT:1:POWER:0
P2SPACEARENAUNIT:1:HP:4
P1RESAVAILABLE:8

---

# AuraBlank_ExpiresNextPhase
#// SOR_138 Force Lightning — the blank lasts "for this phase" only: after the action phase ends and
#// the game crosses regroup into the next action phase, the blanked JTL_085's aura is back and the
#// TIE Bomber is 1/5 again (the 2 damage on Victor Leader itself persists: 2/4 → HP 2).

## GIVEN
CommonSetup: rrk/rrk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2SpaceArena: [JTL_085:1:0 JTL_237:1:0]
WithP1Hand: SOR_138
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:1
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P2SPACEARENAUNIT:1:POWER:1
P2SPACEARENAUNIT:1:HP:5
P2SPACEARENAUNIT:0:DAMAGE:2

---

# PayThree_DealsSix
#// SOR_138 Force Lightning — the damage is always 2 per resource paid: X=3 puts 6 damage on the
#// enemy AT-ST (SOR_232, 6/7 → survives at 1 HP). Spend = 1 (card) + 3 (X) of 10 → 6 left.

## GIVEN
CommonSetup: rrk/rrk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2GroundArena: SOR_232:1:0
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:3

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:6
P1RESAVAILABLE:6

---

# PayZero_BlankButNoDamage
#// SOR_138 Force Lightning — "pay any number" includes ZERO: the target is still blanked (the aura
#// wingman drops to 0/4) but takes no damage, and only the card's own cost is spent (10 → 9 left).

## GIVEN
CommonSetup: rrk/rrk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2SpaceArena: [JTL_085:1:0 JTL_237:1:0]
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:0

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:1:POWER:0
P2SPACEARENAUNIT:1:HP:4
P1RESAVAILABLE:9

---

# PayMax_DefeatsTarget
#// SOR_138 Force Lightning — X may be every remaining ready resource: after the card's own cost
#// (1 of 10), all 9 can be paid for 18 damage, defeating the enemy AT-ST outright. 0 ready left.

## GIVEN
CommonSetup: rrk/rrk/{myResources:10}
P1OnlyActions: true
WithP1GroundArena: SOR_051:1:0
WithP2GroundArena: SOR_232:1:0
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:9

## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1RESAVAILABLE:0

---

# RegroupStartTrigger_NotSilenced
#// SOR_138 Force Lightning — the blank lasts only for the ACTION phase it was played in, so a
#// "When the regroup phase starts" trigger on the blanked unit still fires: P1 blanks their own
#// JTL_198 Fireball (no Force unit in play → no pay step), and at regroup Fireball's self-ping
#// still deals it 1 damage. Intended: phase-duration expiry happens before regroup-start triggers.

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1SpaceArena: JTL_198:1:0
WithP1Hand: SOR_138
WithP1Deck: [SOR_095 SOR_095]
WithP2Deck: [SOR_095 SOR_095]

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass

## EXPECT
PHASE:MAIN
P1SPACEARENAUNIT:0:DAMAGE:1

---

# OfferPool_AllUnitsBothSides
#// SOR_138 Force Lightning — "Choose a unit": the target pool is EVERY unit in play, friendly and
#// enemy, ground and space. The choose is left pending so the offer itself is the assertion.

## GIVEN
CommonSetup: rrk/rrk/{myResources:2}
P1OnlyActions: true
WithP1GroundArena: SOR_095:1:0
WithP1SpaceArena: SOR_141:1:0
WithP2GroundArena: SOR_164:1:0
WithP2SpaceArena: JTL_237:1:0
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0

## EXPECT
P1HASDECISION
P1SELECTABLEEXACT:myGroundArena-0&mySpaceArena-0&theirGroundArena-0&theirSpaceArena-0

---

# NoUnits_PlaysToNoEffect
#// SOR_138 Force Lightning — with no unit anywhere in play the event can still be played: it goes to
#// discard, its cost is spent, and nothing else happens (no pending choice).

## GIVEN
CommonSetup: rrk/rrk/{myResources:1}
P1OnlyActions: true
WithP1Hand: SOR_138

## WHEN
- P1>PlayHand:0

## EXPECT
P1DISCARDCOUNT:1
P1RESAVAILABLE:0
P1NODECISION
