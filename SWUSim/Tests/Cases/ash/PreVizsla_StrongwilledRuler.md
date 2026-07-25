# BudgetDefeat_CreatesPerDefeat
#// ASH_053 Pre Vizsla (Ground, 6/6, cost 8) — When Played: defeat any number of non-leader units with a
#// COMBINED 6-or-less remaining HP; create a Mandalorian token for each one defeated. P2 has two 3/1
#// Stormtroopers (combined 2 HP). P1 defeats both (one at a time), then the loop ends (Pre Vizsla's own
#// 6 HP no longer fits the reduced budget) → 2 Mandalorian tokens created.

## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP2GroundArena: SOR_128:1:0
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>AnswerDecision:theirGroundArena-0

## EXPECT
P2GROUNDARENACOUNT:0
P1GROUNDARENACOUNT:3
P1GROUNDARENAUNIT:0:CARDID:ASH_053

---

# DefeatNone_NoTokens
#// ASH_053 Pre Vizsla — "any number" may be zero. Declining defeats nothing and creates no Mandalorian
#// tokens (only Pre Vizsla enters; the enemy Stormtrooper lives).
## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP2GroundArena: SOR_128:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-
## EXPECT
P2GROUNDARENACOUNT:1
P1GROUNDARENACOUNT:1

---

# DefeatSelf_OneToken
#// ASH_053 Pre Vizsla — she is a legal target of her own When Played (6/6 = exactly the 6-HP budget). With
#// no other units around, P1 selects Pre Vizsla herself: she is defeated and 1 Mandalorian token is created,
#// so only the token remains and Pre Vizsla is in the discard.
## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_T01
P1GROUNDARENAUNIT:0:EXHAUSTED
P1DISCARDCOUNT:1

---

# DamagedHighHpUnit_RemainingHpFits
#// ASH_053 Pre Vizsla — the budget looks at REMAINING HP, not printed HP. An AT-ST (SOR_232, 6/7) with 1
#// damage has 6 remaining HP, so it fits the 6-or-less budget and can be defeated → 1 Mandalorian token.
## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP2GroundArena: SOR_232:1:1
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
## EXPECT
P2GROUNDARENACOUNT:0
P2DISCARDCOUNT:1
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:ASH_T01

---

# DefeatFriendlyUnit_Token
#// ASH_053 Pre Vizsla — "any number of non-leader units" is not limited to enemies; a friendly unit can be
#// chosen too. P1 defeats their own Porg (LOF_254, 1/1) → 1 Mandalorian token; Pre Vizsla stays, Porg goes
#// to P1's discard.
## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP1GroundArena: LOF_254:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:ASH_053
P1GROUNDARENAUNIT:1:CARDID:ASH_T01
P1DISCARDCOUNT:1

---

# LurkingTiePhantom_CannotBeDefeated_NoToken
#// ASH_053 Pre Vizsla — a unit that "can't be defeated by enemy card abilities" (Lurking TIE Phantom
#// SHD_187, 2/2) is not actually defeated even if chosen, so NO Mandalorian token is created for it. The
#// Phantom survives and Pre Vizsla enters alone.
## GIVEN
CommonSetup: brk/rrk/{myResources:8;handCardIds:ASH_053}
WithP2SpaceArena: SHD_187:1:0
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirSpaceArena-0
## EXPECT
P2SPACEARENACOUNT:1
P1GROUNDARENACOUNT:1
P1GROUNDARENAUNIT:0:CARDID:ASH_053
