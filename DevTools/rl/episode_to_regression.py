import argparse
import json
import shutil
from datetime import datetime, timezone
from pathlib import Path
from typing import Any, Dict, List

from bridge_client import BridgeClient, REPO_ROOT


def read_text(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def load_episode(path: Path) -> Dict[str, Any]:
    payload = json.loads(read_text(path))
    if not isinstance(payload, dict):
        raise RuntimeError("Episode file must contain a JSON object.")
    return payload


def load_source_run_config(episode_path: Path) -> Dict[str, Any]:
    run_config_path = episode_path.parent.parent / "run_config.json"
    if not run_config_path.is_file():
        return {}
    payload = json.loads(read_text(run_config_path))
    return payload if isinstance(payload, dict) else {}


def extract_actions(episode: Dict[str, Any], max_steps: int = 0) -> List[Dict[str, Any]]:
    entries = episode.get("actions", [])
    if not isinstance(entries, list):
        raise RuntimeError("Episode JSON missing actions list.")

    actions: List[Dict[str, Any]] = []
    limit = len(entries) if max_steps <= 0 else min(max_steps, len(entries))
    for entry in entries[:limit]:
        action = entry.get("action", {}) if isinstance(entry, dict) else {}
        if not isinstance(action, dict) or not action:
            raise RuntimeError(f"Episode action {len(actions) + 1} has no concrete action payload.")
        clean = {
            "playerID": int(action.get("playerID", 0)),
            "mode": int(action.get("mode", 0)),
            "buttonInput": str(action.get("buttonInput", "")),
            "cardID": str(action.get("cardID", "")),
            "chkInput": action.get("chkInput", []) if isinstance(action.get("chkInput", []), list) else [],
            "inputText": str(action.get("inputText", "")),
        }
        if "resolvedCardID" in action:
            clean["resolvedCardID"] = str(action.get("resolvedCardID", ""))
        if "resolvedCardIDs" in action and isinstance(action.get("resolvedCardIDs"), list):
            clean["resolvedCardIDs"] = [str(card_id) for card_id in action.get("resolvedCardIDs", [])]
        actions.append(clean)
    return actions


def action_key(action: Dict[str, Any]) -> tuple:
    return (
        int(action.get("playerID", 0)),
        int(action.get("mode", 0)),
        str(action.get("buttonInput", "")),
        str(action.get("cardID", "")),
        tuple(str(value) for value in action.get("chkInput", []) if isinstance(action.get("chkInput", []), list)),
        str(action.get("inputText", "")),
    )


def assert_action_is_legal(bridge: BridgeClient, game_name: str, action: Dict[str, Any], step: int) -> None:
    legal = bridge.enumerate_legal_actions(game_name)
    legal_actions = legal.get("actions", []) if isinstance(legal.get("actions", []), list) else []
    legal_keys = {action_key(legal_action) for legal_action in legal_actions}
    if action_key(action) in legal_keys:
        return

    preview = [
        {
            "playerID": legal_action.get("playerID"),
            "mode": legal_action.get("mode"),
            "cardID": legal_action.get("cardID"),
            "resolvedCardID": legal_action.get("resolvedCardID", ""),
        }
        for legal_action in legal_actions[:10]
    ]
    raise RuntimeError(
        "Episode action is not currently legal at step "
        f"{step}. action={json.dumps(action, ensure_ascii=False)} "
        f"legalKind={legal.get('kind', '')} decisionType={legal.get('decisionType', '')} "
        f"legalPlayerID={legal.get('playerID', '')} legalPreview={json.dumps(preview, ensure_ascii=False)}"
    )


def gamestate_path(root: str, game_name: str) -> Path:
    return REPO_ROOT / root / "Games" / game_name / "Gamestate.txt"


def write_expected_final_snapshot(source_final: Path, output_path: Path) -> None:
    with source_final.open("r", encoding="utf-8", newline="") as handle:
        text = handle.read()
    newline = "\r\n" if "\r\n" in text else "\n"
    lines = text.splitlines()
    with output_path.open("w", encoding="utf-8", newline="") as handle:
        handle.write(newline.join(lines) + newline)


def write_text_preserving_newlines(output_path: Path, text: str) -> None:
    newline = "\r\n" if "\r\n" in text else "\n"
    lines = text.splitlines()
    with output_path.open("w", encoding="utf-8", newline="") as handle:
        handle.write(newline.join(lines) + newline)


def main() -> None:
    parser = argparse.ArgumentParser(description="Convert an RL replay episode into a TCGEngine integration fixture.")
    parser.add_argument("--root", default="")
    parser.add_argument("--episode-file", required=True)
    parser.add_argument("--deck-file", required=True)
    parser.add_argument("--slug", required=True)
    parser.add_argument("--steps", type=int, default=0, help="Only replay first N steps. 0 means all episode actions.")
    parser.add_argument("--force", action="store_true", help="Overwrite an existing fixture directory.")
    parser.add_argument("--allow-invalid-actions", action="store_true", help="Convert even if a replay action is not in the current legal action set.")
    parser.add_argument(
        "--allow-reconstructed-start",
        action="store_true",
        help="Allow legacy replays without initialGamestateText to rebuild the start from seed/deck.",
    )
    args = parser.parse_args()

    episode_path = Path(args.episode_file)
    episode = load_episode(episode_path)
    source_run_config = load_source_run_config(episode_path)
    root = args.root or str(episode.get("root", "")) or "AzukiSim"
    slug = args.slug.strip()
    if not slug:
        raise RuntimeError("--slug is required.")

    fixture_dir = REPO_ROOT / "Tests" / "Integration" / root / slug
    if fixture_dir.exists():
        if not args.force:
            raise RuntimeError(f"Fixture already exists: {fixture_dir}. Pass --force to overwrite.")
        shutil.rmtree(fixture_dir)
    fixture_dir.mkdir(parents=True, exist_ok=True)
    fixture_complete = False

    try:
        seed = int(episode.get("seed", 0))
        if seed <= 0:
            raise RuntimeError("Episode JSON must contain a positive seed.")

        deck_text = read_text(Path(args.deck_file))
        actions = extract_actions(episode, max_steps=args.steps)
        game_name = f"rl_fixture_{slug}"
        bridge = BridgeClient(root=root, use_daemon=False)

        initial_gamestate_text = str(episode.get("initialGamestateText", ""))
        if initial_gamestate_text:
            game_dir = gamestate_path(root, game_name).parent
            game_dir.mkdir(parents=True, exist_ok=True)
            write_text_preserving_newlines(fixture_dir / "initial_gamestate.txt", initial_gamestate_text)
            write_text_preserving_newlines(gamestate_path(root, game_name), initial_gamestate_text)
        else:
            if not args.allow_reconstructed_start:
                raise RuntimeError(
                    "Episode is missing initialGamestateText. Re-run training with the updated trainer "
                    "or pass --allow-reconstructed-start for legacy best-effort conversion."
                )
            start = bridge.start_selfplay_game(
                game_name=game_name,
                seed=seed,
                deck_text_p1=deck_text,
                deck_text_p2=deck_text,
                memory_only=False,
            )
            if not start.get("success", False):
                raise RuntimeError(f"start-selfplay-game failed: {start}")

            source_initial = gamestate_path(root, game_name)
            if not source_initial.is_file():
                raise RuntimeError(f"Initial gamestate was not written to disk: {source_initial}")
            shutil.copyfile(source_initial, fixture_dir / "initial_gamestate.txt")

        for index, action in enumerate(actions, start=1):
            if not args.allow_invalid_actions:
                assert_action_is_legal(bridge, game_name, action, index)
            result = bridge.apply_engine_action(game_name, action)
            if not result.get("success", False):
                raise RuntimeError(f"Engine action failed at step {index}: {result}")

        source_final = gamestate_path(root, game_name)
        if not source_final.is_file():
            raise RuntimeError(f"Final gamestate was not written to disk: {source_final}")
        write_expected_final_snapshot(source_final, fixture_dir / "expected_final_gamestate.txt")

        (fixture_dir / "actions.json").write_text(json.dumps(actions, indent=4), encoding="utf-8")
        (fixture_dir / "assertions.json").write_text("[]\n", encoding="utf-8")
        meta = {
            "name": slug,
            "rootName": root,
            "createdAt": datetime.now(timezone.utc).isoformat(),
            "createdBy": "rl-episode-to-regression",
            "sourceEpisode": str(episode_path),
            "sourceDeckFile": str(args.deck_file),
            "sourceRunSeed": source_run_config.get("seed"),
            "episode": episode.get("episode"),
            "episodeSeed": seed,
            "seed": seed,
            "steps": len(actions),
            "initialStateSource": "episode.initialGamestateText" if initial_gamestate_text else "reconstructed",
        }
        (fixture_dir / "meta.json").write_text(json.dumps(meta, indent=4), encoding="utf-8")
        fixture_complete = True
    finally:
        temp_dir = REPO_ROOT / root / "Games" / game_name if "game_name" in locals() else None
        if temp_dir is not None and temp_dir.is_dir():
            shutil.rmtree(temp_dir)
        if not fixture_complete and fixture_dir.is_dir():
            shutil.rmtree(fixture_dir)

    print(json.dumps({"success": True, "fixtureDir": str(fixture_dir), "steps": len(actions)}, indent=2))


if __name__ == "__main__":
    main()
