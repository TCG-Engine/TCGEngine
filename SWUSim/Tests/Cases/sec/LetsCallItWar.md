# Deal3ThenInitiative2
#// SEC_180 Let's Call It War (Event, Aggression, cost 3) — deal 3 to a unit; then if you have the
#//   initiative, may deal 2 to another unit in the same arena.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:2
P1NODECISION

---

# DeclineSecondPing
#// SEC_180 Let's Call It War — deal 3 to a unit; then if you have the initiative, you MAY deal 2 to
#//   another unit. Declining the optional second ping (the Pass button → "PASS") must still finalize
#//   the play and pass the turn. Regression for the "free action" bug where a declined "may" follow-up
#//   skipped the terminal FINISH_PLAY_CARD, leaving the turn with the active player.

## GIVEN
SkipPreGame: true
CommonSetup: rgw/grk/{
  myResources:3;
  handCardIds:SEC_180;
}
WithP1GroundArena: SOR_095:1:0
WithP2GroundArena: SHD_084:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:PASS
- P1>AttackGroundArena:0

## EXPECT
TURNPLAYER:2
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:DAMAGE:0
P2BASEDMG:0

---

# NoInitiative_NoSecondPing
#// SEC_180 Let's Call It War — "Deal 3 to a unit. Then, IF YOU HAVE THE INITIATIVE, you may deal 2 to
#//   another unit in the same arena." Without the initiative, the second ping must NOT be offered:
#//   only the 3 damage lands, the play finalizes, and the turn passes.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:1:DAMAGE:0
TURNPLAYER:2
P1NODECISION

---

# FirstTargetDefeated_SecondStillFires
#// SEC_180 — when the first unit is defeated by the 3 damage, the "if you have the initiative" second
#//   ping still fires on another unit in the same arena. SOR_095 (Battlefield Marine 2/3) dies to the 3;
#//   the 2 then lands on the surviving SOR_046 (Consular Security Force 4/7).

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_095:1:0
WithP1Hand: SEC_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-1
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:SOR_046
P2GROUNDARENAUNIT:0:DAMAGE:2
TURNPLAYER:2
P1NODECISION

---

# FriendlyFirst_SecondToEnemy
#// SEC_180 — the 3 damage may target a FRIENDLY unit; with the initiative the 2 may then go to an
#//   enemy unit in the same arena. SOR_046 (friendly) takes 3, enemy SOR_046 takes 2.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_046:1:0
WithP1Hand: SEC_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:2
TURNPLAYER:2
P1NODECISION

---

# SpaceArena_SecondSameArena
#// SEC_180 — dealing 3 to an enemy SPACE unit lets the follow-up 2 land only on another SPACE unit
#//   (same arena). Both SOR_066 (3/4) survive their damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2SpaceArena: SOR_066:1:0
WithP2SpaceArena: SOR_066:1:0
WithP1Hand: SEC_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
- P1>AnswerDecision:theirSpaceArena-1

## EXPECT
P2SPACEARENAUNIT:0:DAMAGE:3
P2SPACEARENAUNIT:1:DAMAGE:2
TURNPLAYER:2
P1NODECISION

---

# FriendlyFirst_NoInitiative_NoSecondPing
#// SEC_180 Let's Call It War — the initiative gate applies whoever the first target belonged to. P1 has no
#// initiative and puts the 3 damage on its OWN SOR_164 Wampa (4/5, survives); the "then, if you have the
#// initiative" clause is off, so no second target is ever offered and both enemy units stay clean.
#// (The existing no-initiative section aims the 3 at an ENEMY unit — this is the friendly-first mirror.)

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 2
WithP1GroundArena: SOR_164:1:0
WithP2GroundArena: SOR_046:1:0
WithP2SpaceArena: SOR_141:1:0
WithP1Hand: SEC_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P2GROUNDARENAUNIT:0:DAMAGE:0
P2SPACEARENAUNIT:0:DAMAGE:0
TURNPLAYER:2
P1NODECISION

---

# FriendlyFirst_SecondToANOTHERFriendly
#// SEC_180 Let's Call It War — neither half of the ability is restricted to enemy units, so both damages
#// may land on your own board. With the initiative, P1 puts 3 on its own SOR_164 Wampa (4/5, survives) and
#// then 2 on its own SOR_095 in the same arena. (The existing friendly-first section sends the second ping
#// at an ENEMY unit; this proves the second target set isn't enemy-only either.)

## GIVEN
SkipPreGame: true
CommonSetup: rgw/grk/{myResources:3;handCardIds:SEC_180}
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP1GroundArena: SOR_164:1:0
WithP1GroundArena: SOR_095:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P1>AnswerDecision:myGroundArena-1

## EXPECT
P1GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:1:DAMAGE:2

---

# FirstDamagePREVENTED_SecondStillResolves
#// SEC_180 Let's Call It War — "Deal 3 damage to a unit. THEN, if you have the initiative, you may deal 2
#// to another unit in the same arena." The second half is not gated on the first damage actually landing.
#// P1 aims the 3 at P2's SEC_101 Queen Amidala; P2 uses her replacement ("you may defeat another friendly
#// unit that shares a trait with this unit; if you do, prevent that damage") and sacrifices the Official
#// Spy token. Amidala ends on 0 damage and the Spy is gone — yet P1 still gets the second target and puts
#// 2 on the Wampa. Note the Wampa is at index 1 by then: the Spy's death has already re-indexed the arena.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_T01:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: SEC_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1
- P1>AnswerDecision:theirGroundArena-1

## EXPECT
P2GROUNDARENACOUNT:2
P2GROUNDARENAUNIT:0:CARDID:SEC_101
P2GROUNDARENAUNIT:0:DAMAGE:0
P2GROUNDARENAUNIT:1:CARDID:SOR_164
P2GROUNDARENAUNIT:1:DAMAGE:2

---

# SecondTargetOfferIsBuiltAFTERTheFirstDamageSettles
#// SEC_180 Let's Call It War — the offer for "another unit in the same arena" must be built from the board
#// as it stands once the FIRST damage has fully resolved, not from a snapshot taken while a replacement
#// effect is still pending. P1 aims the 3 at SEC_101 Queen Amidala; her controller sacrifices the Official
#// Spy to prevent it. By the time P1 chooses, the Spy is gone and the ONLY remaining target is the Wampa.
#// Previously the list was computed before the sacrifice and also offered the Spy's slot — an index that no
#// longer existed, and picking it silently discarded the 2 damage.

## GIVEN
CommonSetup: rrk/grw/{myResources:3}
WithActivePlayer: 1
WithInitiativePlayer: 1
WithInitiativeClaimed: true
WithP2GroundArena: SEC_101:1:0
WithP2GroundArena: SEC_T01:1:0
WithP2GroundArena: SOR_164:1:0
WithP1Hand: SEC_180

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P2>AnswerDecision:myGroundArena-1

## EXPECT
P1SELECTABLEEXACT:theirGroundArena-1
P2GROUNDARENACOUNT:2
