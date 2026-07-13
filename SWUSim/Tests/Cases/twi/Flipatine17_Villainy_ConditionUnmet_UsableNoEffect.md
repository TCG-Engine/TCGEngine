# TWI_017 "Flipatine" (VILLAINY face) — Ruling 2 on the Villainy side: usable with no Villainy card
# played this phase, but no effects resolve — no Clone token, no base damage, and NO flip. The leader is
# exhausted and stays on the Villainy face. Also confirms a flipped Palpatine is NOT an arena unit
# (P1GROUNDARENACOUNT:0 despite Deployed=true).
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true}
P1OnlyActions: true
## WHEN
- P1>UseLeaderAbility
## EXPECT
P1LEADER:EXHAUSTED
P1LEADER:DEPLOYED
P2BASEDMG:0
P1GROUNDARENACOUNT:0
