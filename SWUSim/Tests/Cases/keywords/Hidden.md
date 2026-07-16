# CanBeAttacked_CapturedRescuedLaterPhase
#// Hidden — captured this phase, rescued in a LATER phase, becomes attackable. P2 plays Witch of the Mist
#// (LOF_154, Hidden). P1 captures her with Take Captive (SHD_131); she leaves play. The round then
#// advances (regroup). Next action phase P2's SOR_095 trades with P1's captor SOR_128 (both die), rescuing
#// the Witch back to P2 as a fresh instance (new UniqueID). P1 then attacks with SEC_080: she is a legal
#// target and is defeated, base untouched.

## GIVEN
CommonSetup: ggk/rrw/{myResources:3;theirResources:2}
WithActivePlayer: 2
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SHD_131
WithP2GroundArena: SOR_095:1:0
WithP2Hand: LOF_154
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
- P2>Claim
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AttackGroundArena:0:0
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0

---

# CanBeAttacked_CapturedRescuedSamePhase
#// Hidden — captured + rescued the SAME phase it was played still becomes attackable. P2 plays Witch of
#// the Mist (LOF_154, Hidden) this phase (would be unattackable). P1 plays Take Captive (SHD_131),
#// choosing its captor (SOR_128) and capturing the Witch (she leaves play). P2's SOR_095 (3/3) attacks
#// the captor SOR_128 (3/1): they trade and both die, so the Witch is RESCUED back to P2 the same phase
#// as a fresh instance (new UniqueID, no played-this-phase marker) and is the ONLY enemy ground unit.
#// P1 then attacks with SEC_080 (3 power): if the rescue cleared the Hidden block she is the target and
#// is defeated; if not, the attack would auto-redirect to P2's base and she'd be untouched. Defeated +
#// base untouched proves rescue clears the block.

## GIVEN
CommonSetup: ggk/rrw/{myResources:3;theirResources:2}
WithActivePlayer: 2
WithP1GroundArena: SOR_128:1:0
WithP1GroundArena: SEC_080:1:0
WithP1Hand: SHD_131
WithP2GroundArena: SOR_095:1:0
WithP2Hand: LOF_154

## WHEN
- P2>PlayHand:0
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-1
- P2>AttackGroundArena:0:0
- P1>AttackGroundArena:0:0

## EXPECT
P2GROUNDARENACOUNT:0
P2BASEDMG:0

---

# CanBeAttacked_SubsequentPhase
#// Hidden — the block is only "the phase it was played." P1 plays Witch of the Mist (LOF_154, 1/3,
#// Hidden) this phase, then the round advances (regroup clears the played-this-phase marker). Next action
#// phase P2 attacks her (SEC_080, 3 power) → she is now a legal target and is defeated. Base is untouched
#// (the attack lands on the unit, not redirected), proving she became attackable.

## GIVEN
CommonSetup: rrw/rrk/{myResources:2}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LOF_154
WithP1Deck: SOR_046 SOR_046 SOR_046 SOR_046
WithP2Deck: SOR_046 SOR_046 SOR_046 SOR_046

## WHEN
- P1>PlayHand:0
- P2>Claim
- P1>Pass
- P1>ResourcePass
- P2>ResourcePass
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:0
P1BASEDMG:0

---

# CantBeAttacked_SamePhasePlayed
#// Hidden (LOF keyword) — "This unit can't be attacked if it was played this phase." P1 plays Attuned
#// Fyrnock (LOF_143, 4/1, Hidden) this phase; it's the only P1 ground unit. P2's attacker (SEC_080, 3/3)
#// has no legal unit target → its attack auto-redirects to P1's base. Fyrnock is untouched (and, at 1 HP,
#// would die if it could be targeted — so DAMAGE:0 + alive proves the block).

## GIVEN
CommonSetup: rrw/rrk/{myResources:2}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LOF_143

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_143
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3

---

# LOF179_CantBeAttacked_SamePhasePlayed
#// Hidden on LOF_179 Aurra Sing (Hidden + Raid 2, unique, 1/4) — confirms the keyword applies to the
#// unique/Raid card too, and Raid (an "while attacking" keyword) doesn't interfere with the can't-be-
#// attacked block. P1 plays Aurra this phase; she's the only P1 ground unit, so P2's SEC_080 (3 power)
#// has no legal unit target and auto-redirects to P1's base. Aurra is untouched.

## GIVEN
CommonSetup: yyk/rrk/{myResources:2}
WithP2GroundArena: SEC_080:1:0
WithP1Hand: LOF_179

## WHEN
- P1>PlayHand:0
- P2>AttackGroundArena:0:0

## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:LOF_179
P1GROUNDARENAUNIT:0:DAMAGE:0
P1BASEDMG:3
