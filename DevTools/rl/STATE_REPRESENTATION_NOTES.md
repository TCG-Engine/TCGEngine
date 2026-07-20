# RL State Representation Notes

These notes summarize what we learned while testing AzukiSim tabular self-play state keys.

## Current Concern

The richer Azuki state key grew too quickly in early training. A 130-episode sample reached roughly 17k states after about 23k decisions, which means many decision points were still creating brand-new table entries. For a tabular policy, that is a warning sign: the bot will spend too much time seeing novel states and too little time reinforcing repeated situations.

The earlier coarse state key converged much better because it merged many similar positions into the same table row.

## Hand Representation

Sorting hand cards before hashing removes hand order permutations:

```text
[A, B, C]
[C, A, B]
```

Both can hash the same after sorting. That helps, but it does not solve hand combination growth:

```text
[A, B, C]
[A, B, D]
[A, C, D]
```

Those are still different exact hands, and exact hand lists can dominate the state space.

## Legal Actions

Legal actions are engine action payloads. For playable hand cards, they are position/object based:

```json
{
  "playerID": 1,
  "mode": 10002,
  "cardID": "myHand-3!FSM!",
  "chkInput": [],
  "resolvedCardID": "S1-STT01-017_Lightning-Orb_S_UC_die"
}
```

For target decisions, they are also current MZ ids, with card metadata when available:

```json
{
  "mode": 100,
  "cardID": "theirGarden-1",
  "resolvedCardID": "..."
}
```

So the action can identify the exact live object, but the current trainer stores logits by legal action index for each state. If a compressed state has different legal action ordering in different games, index `2` may mean different things.

## Important Constraint

The current table shape is effectively:

```text
stateKey -> actionIndex -> logit
```

That makes aggressive state compression risky. If two different hands collapse into the same state, the same action index may refer to different cards.

A more compression-friendly shape would be:

```text
stateKey -> actionKey -> logit
```

Examples:

```text
play:S1-STT01-017_Lightning-Orb_S_UC_die
target:theirGarden:ready:hp1-2:atk3-4
pass
```

That would let the state omit exact hand contents while still letting the policy distinguish "play Lightning Orb" from "play Alley Guy".

## Practical Next Ideas

- Move from action-index logits toward stable action keys or action features.
- Replace exact full hand state with hand features once action keys are stable.
- Keep opponent ready targets semantically rich enough for removal choices: lane, ready/tapped, attack bucket, remaining HP bucket, damage bucket, defender/taunt.
- Drop or heavily summarize tapped non-leader entities for a convergence-focused iteration.
- Always keep leaders represented, since leader damage/status is central.
- Keep high-signal low-cardinality context: phase, next decision type, life buckets, IKZ token/counts.

## Implemented Compact Generation

`AzukiSim:compact-v4` is the current compression-friendly generation. It pairs
an IKZ-aware, context-gated state with `semantic-v2` action keys. Own play,
attack, activation, and target actions still use exact card IDs. Opposing target
actions instead use role, current attack, remaining HP, and a generic threat
value, for example
`target:mzchoose:enemy-garden:atk=3:hp=2:threat=1`. This allows target-choice
learning to transfer to unfamiliar opposing cards with the same combat profile.
Main-phase and response-window passes have distinct keys.

Threat currently defaults to `1` for every card. Per-card threat overrides can
be added to `AzukiRlBotCardThreatValue()` in `AzukiSim/Custom/GameLogic.php` as
we identify engine cards that deserve a different strategic priority.

The compact state deliberately summarizes rather than enumerates exact hands
and boards. Shared fields use buckets for available IKZ, hand count, and life.
Main decisions add board pressure and legal play/attack summaries; response and
chooser decisions receive smaller context-specific summaries. `compact-v2`
included most of these dimensions at every decision and grew to 334,606 states
after only 4,300 games, so it remains loadable but is no longer the default.

`compact-v3`/`semantic-v1` remains loadable and resumable for existing models,
but its opposing target keys contain exact card IDs and do not share logits with
the v4 generation.

The broad goal is not less accurate state. It is more reusable state: strategic situation plus stable action identity, instead of nearly complete visible-game-state memorization.
