# TWI_116 Clone — the copy identity + Clone flag are DURABLE (persist across the per-action gamestate
# serialization). Clone copies an enemy SOR_095, then an UndoCycle (SaveVersion→LoadVersion round-trip)
# reconstructs every zone object from its serialized form. The reloaded unit is still SOR_095 (3/3) and
# still has the Clone trait — proving the IsClone field serializes. (Also ruling: the copy persists as a
# modified copy regardless of the original; the reload does not revert it.)
## GIVEN
CommonSetup: rrk/bbw/{myResources:11;handCardIds:TWI_116}
P1OnlyActions: true
WithP2GroundArena: SOR_095:1:0
## WHEN
- P1>PlayHand:0
- P1>AnswerDecision:theirGroundArena-0
- P1>UndoCycle
## EXPECT
P1GROUNDARENAUNIT:0:CARDID:SOR_095
P1GROUNDARENAUNIT:0:POWER:3
P1GROUNDARENAUNIT:0:HASTRAIT:Clone
