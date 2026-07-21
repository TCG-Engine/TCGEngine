# OnDefense_ForceAttackerDebuff
#// LOF_067 — Sentinel + On Defense (when this unit is attacked, before damage): you may use the Force →
#// the attacker gets -2/-0 for this attack. P1's SOR_046 (3/7) attacks LOF_067 (3/5); P2 uses the Force,
#// so SOR_046 deals only 1 (3-2) to LOF_067 instead of 3. LOF_067 counters for its full 3. This only
#// works because the combat-pause resolves the defender's On Defense reaction before combat damage.

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP2Force: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_067:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:YES

## EXPECT
P2NOFORCE
P2GROUNDARENAUNIT:0:DAMAGE:1
P1GROUNDARENAUNIT:0:DAMAGE:3

---

# OnDefense_DeclineForce_NoDebuff
#// LOF_067 — the On Defense reaction is a "you may". P1's SOR_046 (3/7) attacks Chirrut (3/5); P2 declines
#// to use the Force, so no -2/-0 is applied: Chirrut takes the full 3 and counters for 3. P2 keeps its Force
#// token. Ref: "should not give -2/-0 to the attacker if the Force is not used."

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP2Force: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_067:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0
- P2>AnswerDecision:NO

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3
P2HASFORCE

---

# OnDefense_NoForceToken_NoPrompt
#// LOF_067 — with no Force token, the reaction is unavailable and P2 is not prompted at all. SOR_046 (3/7)
#// attacks Chirrut (3/5) and deals the full 3; Chirrut counters for 3. Ref: "should not prompt the user if
#// they do not have the Force."

## GIVEN
CommonSetup: rrk/ggw
P1OnlyActions: true
WithP1GroundArena: SOR_046:1:0
WithP2GroundArena: LOF_067:1:0

## WHEN
- P1>AttackGroundArena:0:theirGroundArena-0

## EXPECT
P2GROUNDARENAUNIT:0:DAMAGE:3
P1GROUNDARENAUNIT:0:DAMAGE:3
