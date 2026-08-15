# WhenDefeated_BounceLowPower
#// JTL_220 Skyway Cloud Car — When Defeated: may return a non-leader unit with 2 or less power to its
#// owner's hand. JTL_220 (pre-damaged to 1) attacks SOR_046 and dies to the counter; its When Defeated
#// returns the power-2 SOR_225 to P2's hand.

## GIVEN
CommonSetup: bbk/bbk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_220:1:2
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0

## WHEN
- P1>AttackGroundArena:0:0
- P1>AnswerDecision:theirSpaceArena-0

## EXPECT
P1GROUNDARENACOUNT:0
P2SPACEARENACOUNT:0
P2HANDCOUNT:1

---

# Offer_PowerTwoOrLess_NonLeader
#// JTL_220 Skyway Cloud Car — "return a NON-LEADER unit with 2 OR LESS POWER to its owner's hand." Two filters
#// (power and non-leader), controller and arena unrestricted. JTL_220 (pre-damaged to 1 remaining) attacks
#// SOR_046 (3/7) and dies to the counter, opening the When Defeated choice over:
#//   IN  myGroundArena-0    JTL_099 (2/1)  — FRIENDLY, power 2 (index 0 after JTL_220 leaves play)
#//   IN  theirSpaceArena-0  SOR_225 (2/1)  — enemy, power 2, SPACE arena
#//   IN  theirSpaceArena-1  SOR_237 (2/3)  — enemy, power 2, SPACE arena
#//   OUT myGroundArena-1    SOR_095 (3/3)  — power 3
#//   OUT theirGroundArena-0 SOR_046 (3/7)  — power 3
#//   OUT theirGroundArena-1 JTL_011 Major Vonreg, DEPLOYED leader — power 2, but a LEADER (proven present by
#//                          P2GROUNDARENACOUNT:2); the power gate alone would have let it through.
#// The decision is left PENDING so the offer itself is asserted.

## GIVEN
CommonSetup: bbk/brk/{
  myLeader:JTL_001;
  myBase:JTL_019;
  theirBase:SOR_021;
  theirLeader:JTL_011:1:1
}
SkipPreGame: true
P1OnlyActions: true
WithP1GroundArena: JTL_220:1:2
WithP1GroundArena: JTL_099:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_225:1:0
WithP2SpaceArena: SOR_237:1:0

## WHEN
- P1>AttackGroundArena:0:0

## EXPECT
P1HASDECISION
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:1:CARDID:JTL_011
P1SELECTABLEEXACT:myGroundArena-0&theirSpaceArena-0&theirSpaceArena-1
