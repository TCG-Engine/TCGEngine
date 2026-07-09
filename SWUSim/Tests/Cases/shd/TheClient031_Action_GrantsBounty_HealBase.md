# SHD_031 The Client — Action [Exhaust]: choose a unit; for this phase it gains "Bounty — Heal 5
# damage from a base." P1's Client bounties the enemy Battlefield Marine (SOR_095, 3/3); Industrious
# Team (LAW_124, 4/7) defeats it; P1 (the opponent of the bountied unit's controller, CR 13.f) collects
# and heals 5 from their own pre-damaged base.

## GIVEN
CommonSetup: grw/grw/{myBaseDamage:5}
P1OnlyActions: true
WithP1GroundArena: SHD_031:1:0    # The Client (ready) — index 0
WithP1GroundArena: LAW_124:1:0    # Industrious Team — index 1 (the killer)
WithP2GroundArena: SOR_095:1:0    # Battlefield Marine — the bountied victim

## WHEN
- P1>UseUnitAbility:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0
- P1>AttackGroundArena:1:0
- P1>AnswerDecision:YES
- P1>AnswerDecision:myBase-0

## EXPECT
P1GROUNDARENAUNIT:0:EXHAUSTED
P2GROUNDARENACOUNT:0
P1BASEDMG:0
P1GROUNDARENAUNIT:1:DAMAGE:3
