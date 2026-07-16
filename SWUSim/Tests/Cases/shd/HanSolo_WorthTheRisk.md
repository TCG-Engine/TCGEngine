# StolenLandspeeder_DamageDefeatsBeforeWhenPlayed
#// SHD_013 Han Solo (front) — "Action [Exhaust]: Play a unit from your hand. It costs 1 less. Deal 2 damage
#//   to it." — interacting with SHD_161 Stolen Landspeeder (3/2, "When Played: If you played this unit from
#//   your hand, an opponent takes control of it. Bounty - if you own this unit, play it from discard for free
#//   + Exp").
#// The "ideal cheese" would be: let the opponent take control, then damage it under their control so YOU (its
#// owner) collect the bounty for free. It does NOT work: Han's 2 damage resolves FIRST and defeats the 2-HP
#// Landspeeder, so its When Played never fires — the opponent never takes control, and it simply goes to P1's
#// (the owner's) discard. No opponent-controlled unit, no free-bounty replay.

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

---

# HanSolo_Deployed_PlayDiscountDeal2
#// SHD_013 Han Solo (deployed Action) — same play-discounted + deal-2. Deployed (5 resources), the
#// deployed Action plays SOR_229 (cost 3 → 2) at index 1 and deals it 2.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013;myResources:5}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229

## WHEN
- P1>DeployLeader
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:1:CARDID:SOR_229
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# HanSolo_Front_PlayDiscountDeal2
#// SHD_013 Han Solo (front Action [Exhaust]) — "Play a unit from your hand. It costs 1 resource less.
#// Deal 2 damage to it." SOR_229 (cost 3 → 2) is played and takes 2 damage; its discounted cost of 4 (penalized 5, -1) is paid.

## GIVEN
CommonSetup: yyw/yyw/{myLeader:SHD_013}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: SOR_229
WithP1Resources: 4

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:SOR_229
P1GROUNDARENAUNIT:0:DAMAGE:2
P1RESAVAILABLE:0
