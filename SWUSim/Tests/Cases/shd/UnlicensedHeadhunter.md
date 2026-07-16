# ExhaustedDefeated_Heal5OwnBase
#// SHD_165 Unlicensed Headhunter (2-cost 3/2 space, Saboteur) — "While this unit is exhausted, it
#// gains: 'Bounty — Heal 5 damage from your base.'" P2's EXHAUSTED Headhunter is defeated by
#// Munificent Frigate (4 ≥ HP 2); P1 collects — "your base" resolves from the collector's
#// perspective, healing P1's pre-damaged base 5 (5 → 0) with no target choice.

## GIVEN
CommonSetup: grw/grw/{myBaseDamage:5}
P1OnlyActions: true
WithP1SpaceArena: JTL_069:1:0
WithP2SpaceArena: SHD_165:0:0    # exhausted

## WHEN
- P1>AttackSpaceArena:0:0
- P1>AnswerDecision:YES

## EXPECT
P2SPACEARENACOUNT:0
P1BASEDMG:0
P1SPACEARENAUNIT:0:DAMAGE:3
