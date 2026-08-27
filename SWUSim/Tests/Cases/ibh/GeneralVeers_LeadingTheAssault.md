# Reprint088
#// IBH_088 General Veers (reprint of IBH_068) — if Vigilance unit: deal 2 enemy base + heal 2 own base.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: IBH_088
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P1BASEDMG:1
P1NODECISION

---

# WhenPlayed_VigilanceControlled
#// IBH_068 General Veers (Ground, 3/6, Aggression/Villainy, cost 5) — When Played: if you control a
#//   Vigilance unit, deal 2 to an enemy base and heal 2 from your base. P1 controls SOR_063 (Vigilance);
#//   P1's base starts at 3 damage → heals to 1; enemy base takes 2.

## GIVEN
CommonSetup: rrk/bbw/{myResources:5;myBaseDamage:3}
P1OnlyActions: true
WithP1Hand: IBH_068
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:2
P1BASEDMG:1
P1NODECISION

---

# TwinSuns_CasterPicksWhichEnemyBase
#// ⚠ TWIN SUNS SWEEP PASS 2 (2026-08-27) — batch 2, "AN enemy base" names no seat.
#// This dealt to OtherPlayer($player), literally seat 2, so above two seats the caster could never hit
#// any other opponent. Now the caster picks; it auto-resolves invisibly at one eligible opponent, so the
#// two-seat sections above are untouched.
#// P1's opponents are 2 and 4 (3 is a teammate). P1 picks P4: P4's base takes 2 and P2's takes NOTHING —
#// under the old code those swap, so this fails if the fix is reverted.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 6
WithP1Hand: IBH_068
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:P4

## EXPECT
SEATCOUNT:4
P4BASEDMG:2
P2BASEDMG:0
P1BASEDMG:0

---

# TwinSuns_OfferIsBothOpponents_NotTheTeammate
#// Sweep rule 4: assert the PROMPT, never just answer it — a spare answer is silently absorbed, so a
#// section that only answers proves nothing about who was offered. This one deliberately does NOT answer,
#// because P#OPTIONHAS/NOT reads a PENDING decision; answering first leaves nothing to inspect.
#// Rule 5: a menu needs TWO eligible opponents, since at one the picker auto-resolves invisibly.

## GIVEN
CommonSetup: rrk/bbw
SkipPreGame: true
WithTeams: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithP3Base: SOR_019:0
WithP4Base: SOR_019:0
WithP1Resources: 6
WithP1Hand: IBH_068
WithP1GroundArena: SOR_063:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
SEATCOUNT:4
P1OPTIONHAS:P2
P1OPTIONHAS:P4
P1OPTIONNOT:P3
