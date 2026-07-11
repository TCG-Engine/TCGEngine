# SHD_010 Bossk (front, undeployed) — "Action [Exhaust]: Deal 1 damage to a unit with a Bounty. You may
# give it +1/+0 for this phase." P1 uses the action on its own SHD_167 (4/4, printed Bounty — the sole
# Bounty unit, so the target auto-resolves): it takes 1 damage and is buffed to 5 power. Bossk exhausts.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_010}
P1OnlyActions: true
WithP1GroundArena: SHD_167:1:0

## WHEN
- P1>UseLeaderAbility
- P1>AnswerDecision:YES

## EXPECT
P1LEADER:EXHAUSTED
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:POWER:5
