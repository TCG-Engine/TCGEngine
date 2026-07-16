# FirstEventBlanked
#// SOR_089 Relentless (8/8) — "The first event played by each opponent each round loses all abilities."
#// P1 controls Relentless; P2 plays Confiscate (its first event of the round) targeting P1's only upgrade.
#// The event is blanked, so the upgrade (SOR_120 on SEC_080) is NOT defeated.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_089:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_251
WithP2Resources: 1

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:1
P2DISCARDCOUNT:1

---

# NoRelentless_EventResolves
#// SOR_089 Relentless — control: without Relentless, P2's Confiscate resolves normally and defeats P1's
#// upgrade.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_251
WithP2Resources: 1

## WHEN
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:1

---

# SecondEventNotBlanked
#// SOR_089 Relentless — only the FIRST event each round is blanked. P2 plays Confiscate (1st event,
#// blanked → upgrade survives), P1 passes, then P2 plays a second Confiscate (NOT blanked) which defeats
#// the upgrade. The end state (upgrade gone) plus Relentless_FirstEventBlanked together prove "first only."

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1SpaceArena: SOR_089:1:0
WithP1GroundArena: SEC_080:1:0
WithP1GroundArenaUpgrade: 0:SOR_120
WithP2Hand: SOR_251
WithP2Hand: SOR_251
WithP2Resources: 2

## WHEN
- P2>PlayHand:0
- P1>Pass
- P2>PlayHand:0

## EXPECT
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P2DISCARDCOUNT:2
