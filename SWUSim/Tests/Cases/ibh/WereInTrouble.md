# DealsThreeToAUnit
#// IBH_061 We're In Trouble (Event, cost 3, Aggression) — Deal 3 damage to a unit. Enemy 4/7 takes 3
#//   (survives). Single unit on board → target auto-resolves.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_061
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION

---

# Reprint086
#// IBH_086 We're In Trouble (reprint of IBH_061) — deal 3 to a unit. Confirms the duplicate is wired.

## GIVEN
CommonSetup: rrk/bbw/{myResources:3}
P1OnlyActions: true
WithP1Hand: IBH_086
WithP2GroundArena: LAW_124:1:0

## WHEN
- P1>PlayHand:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1NODECISION
