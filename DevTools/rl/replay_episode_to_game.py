import argparse
import json
from pathlib import Path
from typing import Any, Dict, List

from bridge_client import BridgeClient


def _load_episode(path: Path) -> Dict[str, Any]:
    return json.loads(path.read_text(encoding="utf-8"))


def _extract_deck_text(args: argparse.Namespace, episode: Dict[str, Any]) -> str:
    if args.deck_file:
        return Path(args.deck_file).read_text(encoding="utf-8")
    deck_text = episode.get("deckText", "")
    if deck_text:
        return str(deck_text)
    raise RuntimeError("Deck text not found in episode JSON. Pass --deck-file.")


def _replay_actions(
    bridge: BridgeClient, game_name: str, actions: List[Dict[str, Any]], max_steps: int
) -> Dict[str, Any]:
    applied = 0
    last_result: Dict[str, Any] = {}
    for action_entry in actions[:max_steps]:
        action = action_entry.get("action", {})
        if not isinstance(action, dict) or not action:
            raise RuntimeError(f"Episode action at step {applied + 1} has no concrete action payload.")
        result = bridge.apply_engine_action(game_name, action)
        if not result.get("success", False):
            raise RuntimeError(f"Engine action failed at step {applied + 1}: {result}")
        applied += 1
        last_result = result
    return {"applied": applied, "lastResult": last_result}


def main() -> None:
    parser = argparse.ArgumentParser(
        description="Replay a saved RL episode into a concrete Games/<gameName> state for UI/debugging."
    )
    parser.add_argument("--root", default="GrandArchiveSim")
    parser.add_argument("--episode-file", required=True)
    parser.add_argument("--game-name", required=True)
    parser.add_argument("--deck-file", default="")
    parser.add_argument("--seed", type=int, default=None, help="Override episode seed.")
    parser.add_argument("--steps", type=int, default=0, help="Replay only first N steps (0 means all).")
    args = parser.parse_args()

    episode = _load_episode(Path(args.episode_file))
    bridge = BridgeClient(root=args.root)
    deck_text = _extract_deck_text(args, episode)
    seed = int(args.seed) if args.seed is not None else int(episode.get("seed", 0))
    if seed <= 0:
        raise RuntimeError("Invalid seed. Provide --seed or ensure episode has a positive seed.")

    start = bridge.start_selfplay_game(game_name=args.game_name, seed=seed, deck_text_p1=deck_text)
    if not start.get("success", False):
        raise RuntimeError(f"start-selfplay-game failed: {start}")

    actions = episode.get("actions", [])
    if not isinstance(actions, list):
        raise RuntimeError("Episode JSON missing actions list.")
    max_steps = len(actions) if args.steps <= 0 else min(args.steps, len(actions))
    replay = _replay_actions(bridge, args.game_name, actions, max_steps)
    snapshot = bridge.get_game_snapshot(args.game_name, "summary")

    print(
        json.dumps(
            {
                "success": True,
                "root": args.root,
                "gameName": args.game_name,
                "seed": seed,
                "stepsRequested": max_steps,
                "stepsApplied": replay["applied"],
                "gamestateHash": snapshot.get("gamestateHash", ""),
                "phase": snapshot.get("phase", ""),
                "turnNumber": snapshot.get("turnNumber", 0),
                "terminal": snapshot.get("terminal", {}),
            },
            indent=2,
        )
    )


if __name__ == "__main__":
    main()
