# FighterAttack_DiscardSelfDamage
#// JTL_156 Trench Run — Attack with a Fighter; +4/+0 and granted On Attack: discard 2 from the defender's
#// deck, deal the cost difference (unpreventable) to this unit. SOR_237 (2 power) gets +4 → 6, mills
#// SOR_225(cost 1)/SOR_237(cost 2) from P2's deck (diff 1 → 1 self-damage), then hits P2's base for 6.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_156
WithP1Resources: 5
WithP1SpaceArena: SOR_237:1:0
WithP2Deck: SOR_225
WithP2Deck: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:6
P1SPACEARENAUNIT:0:DAMAGE:1
P2DECKCOUNT:0

---

# UnpreventableSelfDamage
#// JTL_156 Trench Run — the granted self-damage is UNPREVENTABLE. Attacker SHD_187 Lurking TIE Phantom
#// ("can't be captured, damaged, or defeated by enemy card abilities") gets +4 and, with Raid 2 while
#// attacking, deals 2+2+4 = 8 to P2's base; the milled SOR_225(1)/SOR_237(2) cost-difference of 1 is dealt
#// to the phantom (damage 1) despite its damage-prevention text.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_156
WithP1Resources: 5
WithP1SpaceArena: SHD_187:1:0
WithP2Deck: SOR_225
WithP2Deck: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:8
P1SPACEARENAUNIT:0:DAMAGE:1
P2DECKCOUNT:0

---

# SelfDamageDefeatsBeforeCombat
#// JTL_156 Trench Run — the granted self-damage is dealt BEFORE combat damage, so a fragile Fighter can be
#// defeated first and deal no combat damage. SOR_225 TIE/ln Fighter (2/1) gets +4 → 6 power, but the
#// cost-difference-1 self-damage (from milled SOR_225(1)/SOR_237(2)) defeats it (1 HP) before it hits the
#// base: P2's base takes 0 and the attacker is gone.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1Hand: JTL_156
WithP1Resources: 5
WithP1SpaceArena: SOR_225:1:0
WithP2Deck: SOR_225
WithP2Deck: SOR_237

## WHEN
- P1>PlayHand:0

## EXPECT
P2BASEDMG:0
P1SPACEARENACOUNT:0
P2DECKCOUNT:0
