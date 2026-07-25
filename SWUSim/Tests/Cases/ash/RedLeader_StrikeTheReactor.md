# SupportGrantsCrossArena_GroundUnitAttacksSpace
#// ASH_037 Red Leader (Space, 6/6, Support) grants its "may attack units in either arena" ability to the
#// unit chosen via Support. A friendly GROUND unit (SEC_080 3/3) is chosen; the granted cross-arena reach
#// lets it attack the enemy SPACE unit SOR_225 (2/1), defeating it. SEC_080 takes 2 counter and survives.
## GIVEN
CommonSetup: gyw/gyw/{myResources:10;handCardIds:ASH_037}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:0
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# SupportGrantsCrossArena_SpaceUnitAttacksGround
#// Symmetric grant: a friendly SPACE unit (JTL_069 4/7) chosen via Support gains cross-arena reach and
#// attacks the enemy GROUND unit SOR_128 (3/1), defeating it. JTL_069 takes 3 counter and survives.
## GIVEN
CommonSetup: gyw/gyw/{myResources:10;handCardIds:ASH_037}
WithP1SpaceArena: JTL_069:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: SOR_225:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P1SPACEARENAUNIT:0:CARDID:JTL_069
P1SPACEARENAUNIT:0:DAMAGE:3

---

# RedLeaderCrossArena_SentinelInOtherArenaDoesNotRestrict
#// A Sentinel restricts only attackers in ITS OWN arena. Red Leader is in space; the enemy Sentinel
#// (SOR_063) sits in the GROUND arena, so it does NOT restrict Red Leader. Red Leader attacks the
#// non-Sentinel ground unit SOR_128 (3/1) cross-arena and defeats it; the ground Sentinel is untouched.
## GIVEN
CommonSetup: gyw/gyw
WithP1SpaceArena: ASH_037:1:0
WithP2GroundArena: [SOR_063:1:0 SOR_128:1:0]
P1OnlyActions: true
## WHEN
- P1>AttackSpaceArena:0:G1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_063
P1SPACEARENAUNIT:0:CARDID:ASH_037
P1SPACEARENAUNIT:0:DAMAGE:3

---

# SupportGroundUnit_SpaceSentinelInOtherArenaDoesNotRestrict
#// The supported GROUND unit is not restricted by the enemy space Sentinel (SOR_066), which is in the
#// other arena. With granted cross-arena reach, SEC_080 attacks the non-Sentinel space unit SOR_225 (2/1)
#// and defeats it; the space Sentinel is left alone.
## GIVEN
CommonSetup: gyw/gyw/{myResources:10;handCardIds:ASH_037}
WithP1GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
WithP2SpaceArena: [SOR_066:1:0 SOR_225:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirSpaceArena-1
## EXPECT
P2SPACEARENACOUNT:1
P2SPACEARENAUNIT:0:CARDID:SOR_066
P1GROUNDARENAUNIT:0:CARDID:SEC_080
P1GROUNDARENAUNIT:0:DAMAGE:2

---

# SupportSaboteurSpaceUnit_IgnoresSentinelAndReachesEitherArena
#// A supported SPACE Saboteur (SHD_151) ignores Sentinel AND gains cross-arena reach, so every enemy unit
#// in both arenas plus the enemy base are legal targets — even with Sentinels (SOR_063 ground, SOR_066
#// space) present.
## GIVEN
CommonSetup: gyw/gyw/{myResources:10;handCardIds:ASH_037}
WithP1SpaceArena: SHD_151:1:0
WithP2GroundArena: [SOR_063:1:0 SOR_128:1:0]
WithP2SpaceArena: [SOR_066:1:0 SOR_225:1:0]
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:mySpaceArena-0
## EXPECT
P1SELECTABLEEXACT:theirBase-0&theirGroundArena-0&theirGroundArena-1&theirSpaceArena-0&theirSpaceArena-1

---

# SupportSaboteurGroundUnit_AttacksBaseThroughSentinel
#// A supported GROUND Saboteur (SOR_143 3/4) ignores the enemy ground Sentinel (SOR_063) and attacks the
#// enemy base directly, dealing its 3 combat damage.
## GIVEN
CommonSetup: gyw/gyw/{myResources:10;handCardIds:ASH_037}
WithP1GroundArena: SOR_143:1:0
WithP2GroundArena: SOR_063:1:0
WithP2SpaceArena: SOR_066:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirBase-0
## EXPECT
P2BASEDMG:3
