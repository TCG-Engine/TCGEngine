# Action_DrawAfterFO
#// JTL_134 General Hux — Action [Exhaust]: If you played a First Order card this phase, draw a card. P1
#// plays the FO unit JTL_236, then uses Hux's action to draw.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_134:1:0
WithP1Hand: JTL_236
WithP1Resources: 5
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# RaidAura
#// JTL_134 General Hux — Each other friendly First Order unit gains Raid 1. The FO unit JTL_236 (power 1)
#// attacks SOR_046 and, with Raid 1, deals 1+1=2 damage.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_134:1:0
WithP1GroundArena: JTL_236:1:0
WithP2GroundArena: SOR_046:1:0

## WHEN
- P1>AttackGroundArena:1:0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# SimulateRequestBoundary_PlayedFOThisPhaseSurvivesRoundTrip
#// JTL_134 General Hux — "if you played a First Order card this phase" is a flag written by one action and
#// read by a later one; in production those are two separate requests, so the flag must live in the
#// gamestate. Mirrors Action_DrawAfterFO with the boundary between the play and the unit ability.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_134:1:0
WithP1Hand: JTL_236
WithP1Resources: 5
WithP1Deck: SOR_128

## WHEN
- P1>PlayHand:0
- P1>SimulateRequestBoundary
- P1>UseUnitAbility:myGroundArena-0

## EXPECT
P1HANDCOUNT:1
P1DECKCOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED

---

# Action_DrawAfterFO_PlayedAsPILOT
#// ⚠ FAMILY SWEEP (2026-09-17, from the TWI_017 "Craving Power" report): "played a First Order CARD"
#// covers any card type, but SWU_PLAYED_FO was armed only in the UNIT-ENTRY branch — which explicitly
#// excludes "a Piloting card played as a pilot".
#// ⚠ AND FOR THIS TRAIT THAT IS THE ONLY NON-UNIT PLAY THERE IS: no Event or Upgrade card in the whole
#// card pool carries the First Order trait (checked 2026-09-17), so a First Order PILOT play is the
#// entire uncovered surface. JTL_035 Tam Ryvora is First Order + Pilot; played onto a friendly Vehicle
#// she attaches as a pilot (an upgrade-shaped play), and Hux's Action must still see a FO card played.
#// The sibling Action_DrawAfterFO section plays a FO UNIT, which is why it always passed.
## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_134:1:0
WithP1SpaceArena: SOR_060:1:0
WithP1Hand: JTL_035
WithP1Resources: 5
WithP1Deck: SOR_128
## WHEN
- P1>PlayHand:0
#// "Play as Unit or Pilot?" — take the PILOT branch, then pick the host Vehicle.
- P1>AnswerDecision:Pilot
- P1>AnswerDecision:mySpaceArena-0
- P1>UseUnitAbility:myGroundArena-0
## EXPECT
#// Tam Ryvora leaves hand as a pilot upgrade, and Hux's Action then DRAWS — so the hand holds the drawn
#// card (1), not 0. The deck emptying is what proves the draw.
P1HANDCOUNT:1
P1DECKCOUNT:0
P1GROUNDARENAUNIT:0:EXHAUSTED
P1SPACEARENAUNIT:0:UPGRADECOUNT:1
