# SHD_013 Han Solo (front) — "Action [Exhaust]: Play a unit from your hand. It costs 1 less. Deal 2 damage
#   to it." — interacting with SHD_161 Stolen Landspeeder (3/2, "When Played: If you played this unit from
#   your hand, an opponent takes control of it. Bounty - if you own this unit, play it from discard for free
#   + Exp").
# The "ideal cheese" would be: let the opponent take control, then damage it under their control so YOU (its
# owner) collect the bounty for free. It does NOT work: Han's 2 damage resolves FIRST and defeats the 2-HP
# Landspeeder, so its When Played never fires — the opponent never takes control, and it simply goes to P1's
# (the owner's) discard. No opponent-controlled unit, no free-bounty replay.

## GIVEN
CommonSetup: rrk/rrk/{myLeader:SHD_013;myResources:5}
P1OnlyActions: true
WithP1Hand: SHD_161

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:0
P1DISCARDCOUNT:1
P1DISCARDUNIT:0:CARDID:SHD_161
P1LEADER:EXHAUSTED
