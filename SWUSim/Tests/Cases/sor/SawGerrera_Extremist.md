# NoSaw_NoSurcharge
#// SOR_153 Saw Gerrera — control: without Saw in play, an opponent's event carries no base surcharge.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SEC_080:1:0
WithP2Hand: SOR_251
WithP2Resources: 1

## WHEN
- P2>PlayHand:0

## EXPECT
P2BASEDMG:0
P2DISCARDCOUNT:1

---

# OpponentEvent_DamagesOwnBase
#// SOR_153 Saw Gerrera (5/4) — "As an additional cost for each opponent to play an event, they must deal
#// 2 damage to their base." P1 controls Saw; P2 plays an event (Confiscate, which fizzles with no upgrades
#// in play), so P2's base takes 2 from the surcharge.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_153:1:0
WithP2Hand: SOR_251
WithP2Resources: 1

## WHEN
- P2>PlayHand:0

## EXPECT
P2BASEDMG:2
P2DISCARDCOUNT:1
