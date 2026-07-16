# GrantsSentinel
#// SEC_231 Implicate (event) — Choose a unit; for this phase it gains Sentinel (and a granted "when
#//   attacked, create a Spy"). P1 plays Implicate on its SOR_046 → SOR_046 gains Sentinel.

## GIVEN
CommonSetup: yyk/grk/{myResources:2;handCardIds:SEC_231}
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:HASKEYWORD:Sentinel

---

# SpyWhenAttacked
#// SEC_231 Implicate — the granted "When this unit is attacked: create a Spy token." P1 plays Implicate
#//   on SOR_046 (gains Sentinel + the marker). P2's SOR_128 then attacks SOR_046 (forced by Sentinel); the
#//   granted On Defense fires → P1 gets a Spy token. SOR_046 (3/7) survives the 3; SOR_128 (3/1) dies to
#//   the 3 counter. Turn alternates normally (initiative unclaimed), so P2 acts after P1's play.

## GIVEN
CommonSetup: yyk/grk/{myResources:2;handCardIds:SEC_231}
WithActivePlayer: 1
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: SOR_128:1:0

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0
- P2>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:1:CARDID:SEC_T01
P2GROUNDARENACOUNT:0
P1GROUNDARENAUNIT:0:DAMAGE:3
