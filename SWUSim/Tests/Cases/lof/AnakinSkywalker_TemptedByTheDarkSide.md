# Deployed_Action_PlayVillainyNonUnit
#// LOF_018 Anakin Skywalker (deployed) — Action [use the Force]: play a Villainy non-unit card from
#// your hand, ignoring its aspect penalties. Anakin spends the Force and plays the Villainy event
#// SHD_243 (cost 1); it goes to discard.

## GIVEN
CommonSetup: bgw/brk/{
  myLeader:LOF_018;
  myBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1GroundArena: LOF_018:1:0
WithP1Hand: SHD_243
WithP1Resources: 3

## WHEN
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1NOFORCE
P1HANDCOUNT:0
P1DISCARDCOUNT:1

---

# PlayVillainyIgnorePenalty
#// LOF_018 Anakin Skywalker — Action [Exhaust, use the Force]: Play a Villainy non-unit card from your hand,
#// ignoring its aspect penalties. Anakin's deck is Heroism/Vigilance, so LOF_239 (Villainy, cost 2) would
#// normally cost 4 (off-aspect +2). With the penalty ignored, P1 plays it for 2 onto Plo Koon: +2 Experience
#// then 2 damage → 8/10 with 2 damage.

## GIVEN
CommonSetup: bgw/bbk/{
  myLeader:LOF_018;
  myBase:SOR_021;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Force: true
WithP1Hand: LOF_239
WithP1Resources: 2
WithP1GroundArena: LOF_050:1:0

## WHEN
- P1>UseLeaderAbility

## EXPECT
P1HANDCOUNT:0
P1GROUNDARENAUNIT:0:POWER:8
P1GROUNDARENAUNIT:0:DAMAGE:2
P1NOFORCE
