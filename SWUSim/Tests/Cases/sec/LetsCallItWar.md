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
