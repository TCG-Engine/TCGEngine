# GrandArchiveSim RL MVP

This folder contains a minimal deterministic self-play RL prototype for `GrandArchiveSim`.

## Deck text format

Use the same free-text format already supported by `GrandArchiveSim/Custom/DeckTextParser.php`:

```text
# Material Deck
1 Spirit of Serene Fire
1 Some Regalia

# Main Deck
4 Card Name A
4 Card Name B
...
```

## Train

```bash
python DevTools/rl/train_selfplay.py --root GrandArchiveSim --deck-file path/to/deck.txt --episodes 100 --seed 123 --max-steps 1000
```

Artifacts are written under `DevTools/rl/artifacts/runs/<run_id>/`.

## Evaluate

```bash
python DevTools/rl/eval_selfplay.py --root GrandArchiveSim --deck-file path/to/deck.txt --checkpoint DevTools/rl/artifacts/runs/<run_id>/checkpoints/latest.json --matches 50 --seed 123 --max-steps 1000
```

## Replay an episode into `Games/<gameName>` for UI debugging

```bash
python DevTools/rl/replay_episode_to_game.py --root GrandArchiveSim --episode-file DevTools/rl/artifacts/runs/<run_id>/replays/episode_0001.json --deck-file path/to/deck.txt --game-name rl_debug_0001
```

Optional partial replay:

```bash
python DevTools/rl/replay_episode_to_game.py --root GrandArchiveSim --episode-file ... --deck-file path/to/deck.txt --game-name rl_debug_0001 --steps 15
```
