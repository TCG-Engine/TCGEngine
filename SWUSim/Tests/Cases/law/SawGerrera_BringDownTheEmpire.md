# FrontAttackBuffSelfDefeat
#// LAW_001 Saw Gerrera (leader front) — "Action [Exhaust]: Attack with a unit. It gets +2/+0 and gains
#// Overwhelm for this attack. After completing this attack, defeat it." SEC_080 (3/3) is the only ready
#// unit and P2 has no units, so it auto-attacks the base for 3+2 = 5, then is defeated.

## GIVEN
CommonSetup: rgw/grw/{
  myLeader:LAW_001;
  myBase:SOR_025
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SEC_080:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P2BASEDMG:5
P1GROUNDARENACOUNT:0
