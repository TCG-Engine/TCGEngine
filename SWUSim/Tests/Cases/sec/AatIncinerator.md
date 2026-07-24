# FriendlyHit_NoSelfBase
#// SEC_169 AAT Incinerator — if a friendly unit IS damaged by the ability, no self-base damage. Hit one
#//   friendly SOR_046 → no penalty.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP1Hand: SEC_169

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:1
P1BASEDMG:0
P1NODECISION

---

# NoFriendlyHit_SelfBase
#// SEC_169 AAT Incinerator (Unit, Aggression, cost 5) — When Played: deal 1 to each of up to 4 OTHER
#//   ground units; if no friendly units were damaged, deal 2 to your base. Hit two enemies only → 2 to own base.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_169

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P1BASEDMG:2
P1NODECISION

---

# UpTo4Enemies_SpaceNotSelectable_SelfBase
#// SEC_169 AAT Incinerator — "up to 4 GROUND units" only: an enemy space unit (SHD_101) is NOT a legal
#//   target. P2 fields 4 ground + 1 space. All 4 ground are selectable; choosing all 4 (all enemy → no
#//   friendly damaged) deals 1 to each and 2 to P1's own base; the space unit is untouched.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_239:1:0
WithP2GroundArena: SOR_232:1:0
WithP2SpaceArena: SHD_101:1:0
WithP1Hand: SEC_169

## WHEN
- P1>PlayHand:0

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2&theirGroundArena-3

---

# UpTo4Enemies_ChooseAll_SelfBase
#// SEC_169 AAT Incinerator — continuation: pick all 4 enemy ground units. Each takes 1 damage; the
#//   space unit stays clean; no friendly unit damaged → 2 to P1's own base.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_095:1:0
WithP2GroundArena: SOR_239:1:0
WithP2GroundArena: SOR_232:1:0
WithP2SpaceArena: SHD_101:1:0
WithP1Hand: SEC_169

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0&theirGroundArena-1&theirGroundArena-2&theirGroundArena-3

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:1
P2GROUNDARENAUNIT:1:DAMAGE:1
P2GROUNDARENAUNIT:2:DAMAGE:1
P2GROUNDARENAUNIT:3:DAMAGE:1
P2SPACEARENAUNIT:0:DAMAGE:0
P1BASEDMG:2
P1NODECISION

---

# ChooseNothing_SelfBase
#// SEC_169 AAT Incinerator — "up to 4" allows choosing zero units. An enemy ground unit is present but
#//   declining (Choose Nothing) damages nothing; since no friendly unit was damaged, P1's base takes 2.

## GIVEN
CommonSetup: rrk/grw/{myResources:5}
P1OnlyActions: true
WithP2GroundArena: SOR_164:1:0
WithP1Hand: SEC_169

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:2
P1NODECISION
