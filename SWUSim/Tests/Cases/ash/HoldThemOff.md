# SplitPowerInArena
#// ASH_139 Hold Them Off (Event, cost 4) — Choose a friendly unit; it deals damage equal to its power
#// divided among any number of units in its arena. P1 picks SOR_046 (3 power) and assigns all 3 to the
#// enemy SEC_080 (3/3) in the ground arena, defeating it.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_139}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:3
## EXPECT
P2GROUNDARENACOUNT:0

---

# SplitAcrossTwoUnits
#// ASH_139 Hold Them Off — the power may be divided among multiple units in the arena. SOR_046 (3 power)
#// puts 2 on SEC_080 and 1 on SOR_128 (3/1), defeating SOR_128 while SEC_080 survives with 2.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_139}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0:2,theirGroundArena-1:1
## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SEC_080
P2GROUNDARENAUNIT:0:DAMAGE:2

---

# DistributeAcrossFriendlyAndSelfAndEnemy
#// ASH_139 Hold Them Off — the damage may be divided among ANY units in the arena, including the source
#// itself and other friendly units. With two friendly units in play, P1 first chooses the source SOR_046
#// (3 power), then assigns 1 to itself, 1 to the friendly SOR_095, and 1 to the enemy SEC_080.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_139}
WithP1GroundArena: SOR_046:1:0
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-0:1,myGroundArena-1:1,theirGroundArena-0:1
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:0:DAMAGE:1

---

# SpaceArenaDivideAndDefeat
#// ASH_139 Hold Them Off — the effect is confined to the source unit's arena. A friendly space unit
#// (SOR_237, 2 power) puts both damage on the enemy space SOR_060 (2/1), defeating it, while the enemy
#// ground SEC_080 (a different arena) is untouched.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_139}
WithP1SpaceArena: SOR_237:1:0
WithP2SpaceArena: SOR_060:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0:2
## EXPECT
P2SPACEARENACOUNT:0
P2GROUNDARENAUNIT:0:DAMAGE:0

---

# ChooseZeroTargets
#// ASH_139 Hold Them Off — the "divided among any number of units" may be zero. P1 plays the event,
#// SOR_046 is the source, but assigns no damage. Nothing is damaged and the event is spent to the discard.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_139}
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:0:DAMAGE:0
P1DISCARDCOUNT:1

---

# NoFriendlyUnits_Fizzles
#// ASH_139 Hold Them Off — the event needs a friendly unit to be the damage source. With no friendly units
#// in play it does nothing: the card is spent to the discard and the enemy takes no damage.
## GIVEN
CommonSetup: ggk/ggk/{myResources:4;handCardIds:ASH_139}
WithP2GroundArena: SEC_080:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1DISCARDCOUNT:1
P2GROUNDARENAUNIT:0:DAMAGE:0
