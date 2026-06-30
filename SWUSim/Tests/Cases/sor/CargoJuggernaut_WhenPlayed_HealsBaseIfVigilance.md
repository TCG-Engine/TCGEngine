# SOR_068 Cargo Juggernaut (4/6, Ground, Vigilance) — Shielded + When Played: If you control
# another [Vigilance] unit, heal 4 damage from your base. P1 already controls 2-1B (SOR_059,
# Vigilance), so the condition holds: the base (pre-damaged 4) heals to 0. The Juggernaut also
# enters with a Shield (Shielded). Two entry triggers (Shielded + When Played) → order them
# via the EffectStack choice; both then resolve automatically (When Played is not optional).

## GIVEN
CommonSetup: ggw/ggw/{myResources:10;myBaseDamage:4;handCardIds:SOR_068}
P1OnlyActions: true
WithP1GroundArena: SOR_059:1:0    # another Vigilance unit (2-1B) — idx 0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:EffectStack-0

## EXPECT
P1BASEDMG:0
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:SHIELDCOUNT:1
