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

---

# SawsControllerPaysNothing_OwnEventCostsNoBaseDamage
#// SOR_153 Saw Gerrera — the SCOPE gate in "for each OPPONENT to play an event". The surcharge is not a
#// table-wide event tax: the player who CONTROLS Saw plays events for free. P1 controls Saw and plays
#// Confiscate itself; P1's base must be untouched (and so must P2's — the damage is "to THEIR base",
#// the payer's, so a mis-seated applier would show up here as a hit on the wrong base).
#// COVERAGE: offer=N/A (a static play-time surcharge, not a targeting effect — nothing is ever offered
#//           or chosen; the two-sided gate is asserted with this section + NoSaw_NoSurcharge instead) ·
#//           decline=N/A (an ADDITIONAL COST, not a "you may" — the payer never gets the choice, which
#//           is exactly what SurchargeIsUnavoidableAndCanDefeatTheOpponentsBase pins) ·
#//           control=NoSaw_NoSurcharge + this section (the surcharge is read off who CONTROLS a Saw in
#//           play at play time, so moving Saw to the other side of the table moves the tax with him:
#//           these two sections are the same event played by each side of one Saw) ·
#//           boundary=OpponentEvent_DamagesOwnBase (base 0 -> 2, survives) vs
#//           SurchargeIsUnavoidableAndCanDefeatTheOpponentsBase (base 28 -> 30, defeated) ·
#//           reqboundary=N/A (the cost is paid synchronously inside the play; no decision is queued)

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: SOR_153:1:0
WithP1Hand: SOR_251
WithP1Resources: 1

## WHEN
- P1>PlayHand:0

## EXPECT
P1BASEDMG:0
P2BASEDMG:0
P1DISCARDCOUNT:1

---

# NonEventPlay_NoSurcharge
#// SOR_153 Saw Gerrera — the CARD-TYPE gate in "to play an EVENT". With Saw on P1's board, P2 plays a
#// UNIT instead of an event: no base damage. Both existing sections vary only whether Saw is present,
#// so an implementation that taxed every opponent play — units and upgrades included — passes them
#// both; this is the section that separates the two readings.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_153:1:0
WithP2Hand: SOR_095
WithP2Resources: 6    # 2 printed + the uncovered-aspect surcharge

## WHEN
- P2>PlayHand:0

## EXPECT
P2BASEDMG:0
P1BASEDMG:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_095

---

# SurchargeIsUnavoidableAndCanDefeatTheOpponentsBase
#// SOR_153 Saw Gerrera — the top of the boundary pair, and the proof that this is an ADDITIONAL COST
#// rather than an optional or a self-limiting one. P2's base (Dagobah Swamp, 30 HP) is already on 28
#// damage, so the 2 it must deal to itself to play an event is exactly lethal. P2 gets no choice about
#// paying and no clamp that stops short of its own defeat: the event is played, the base reaches 30,
#// and P1 wins. Paired with OpponentEvent_DamagesOwnBase, which is the same surcharge at 0 -> 2.

## GIVEN
CommonSetup: ggw/brw/{
  theirBase:SOR_021;
  theirBaseDamage:28
}
SkipPreGame: true
WithActivePlayer: 2
WithP1GroundArena: SOR_153:1:0
WithP2Hand: SOR_251
WithP2Resources: 1

## WHEN
- P2>PlayHand:0

## EXPECT
P2BASEDMG:30
P2DISCARDCOUNT:1
P1WIN
