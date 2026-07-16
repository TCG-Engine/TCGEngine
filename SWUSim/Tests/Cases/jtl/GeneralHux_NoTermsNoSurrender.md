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
