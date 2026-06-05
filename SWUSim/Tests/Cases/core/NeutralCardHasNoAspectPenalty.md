## GIVEN
# ASH_259 (LEP Ratcatcher) is neutral: no aspects, cost 1
CommonSetup: grw/ggk/{myResources:1;handCardIds:ASH_259}

## WHEN
- P1>PlayHand:0

## EXPECT
# Neutral card plays at base cost only — 0 penalty, all 1 resource spent
P1GROUNDARENACOUNT:1
P1HANDCOUNT:0
P1RESAVAILABLE:0
