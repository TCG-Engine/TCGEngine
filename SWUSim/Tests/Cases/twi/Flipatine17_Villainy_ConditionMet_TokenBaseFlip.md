# TWI_017 "Flipatine" (VILLAINY face, Deployed=true, no arena unit) — Action [Exhaust]: If you played a
# Villainy card this phase, create a Clone Trooper token, deal 2 to each enemy base, then flip back. P1
# plays a Villainy unit (SOR_128) to arm SWU_PLAYED_VILLAINY, then uses the Action: creates a Clone
# Trooper (TWI_T02), deals 2 to P2's base, and flips to the Heroism face (Deployed=false). Stays exhausted.
## GIVEN
CommonSetup: brk/bbw/{myLeader:TWI_017:1;myLeaderFlipped:true;myResources:4;handCardIds:SOR_128}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>UseLeaderAbility
## EXPECT
P2BASEDMG:2
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:TWI_T02
P1LEADER:EXHAUSTED
P1LEADER:NOTDEPLOYED
