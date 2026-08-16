# WhenPlayed_HealAllAnd2Shields
#// SHD_049 The Mandalorian (6-cost 5/6 ground) — Sentinel + "When Played: You may heal all damage from a
#// unit that costs 2 or less and give 2 Shield tokens to it." The friendly SHD_095 (cost 1, 2 damage) is
#// fully healed and gains 2 Shields.
#// COVERAGE: offer=both sections raise a real prompt with a single legal cost-≤2 target, which is itself
#//           the assertion that a "you may" does NOT auto-resolve; the cost-≤2 filter's contents are not
#//           separately asserted with SELECTABLE (deferred — see the report note) ·
#//           decline=WhenPlayed_Declined_NoHealNoShields (the '-' branch: no heal, no Shields, and no
#//           consolation Shield on the Mandalorian either) ·
#//           boundary=WhenPlayed_HealAllAnd2Shields (accept → damage 0 + 2 Shields) vs
#//           WhenPlayed_Declined_NoHealNoShields (refuse → the identical board left untouched at 2
#//           damage / 0 Shields); the pair is what proves the heal was the ability and not an artifact of
#//           playing him · control=N/A (the Shields are placed at resolution on a unit chosen then; no
#//           persistent controller-bound marker is created) · reqboundary=N/A (heal and both Shield
#//           tokens are applied in the single When-Played resolution).

## GIVEN
CommonSetup: bbw/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_049
WithP1GroundArena: SHD_095:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:myGroundArena-0

## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SHD_095
P1GROUNDARENAUNIT:0:DAMAGE:0
P1GROUNDARENAUNIT:0:SHIELDCOUNT:2

---

# WhenPlayed_Declined_NoHealNoShields
#// SHD_049 The Mandalorian — the "You may" branch is refusable even with a perfectly legal target on the
#// board. P1 declines: SHD_095 keeps its 2 damage and gets no Shield tokens, and the Mandalorian itself
#// is not shielded as a consolation. He still enters play normally at index 1.

## GIVEN
CommonSetup: bbw/bbw/{myResources:6}
P1OnlyActions: true
WithP1Hand: SHD_049
WithP1GroundArena: SHD_095:1:2

## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:-

## EXPECT
P1GROUNDARENACOUNT:2
P1GROUNDARENAUNIT:0:CARDID:SHD_095
P1GROUNDARENAUNIT:0:DAMAGE:2
P1GROUNDARENAUNIT:0:SHIELDCOUNT:0
P1GROUNDARENAUNIT:0:UPGRADECOUNT:0
P1GROUNDARENAUNIT:1:CARDID:SHD_049
P1GROUNDARENAUNIT:1:SHIELDCOUNT:0
