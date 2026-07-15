# TS26_015 C-3P0 (Unit 2/5, cost 2, Droid, Vigilance/Command) — When Played: an opponent takes control of
# this unit. P1 plays C-3P0; it enters P1's play then transfers to P2's control (P2's arena), leaving P1's
# ground arena empty.
## GIVEN
CommonSetup: gbw/rrk/{handCardIds:TS26_015;myResources:6}
P1OnlyActions: true
## WHEN
- P1>PlayHand:0
## EXPECT
P1GROUNDARENACOUNT:0
P2GROUNDARENACOUNT:1
P2GROUNDARENAUNIT:0:CARDID:TS26_015
