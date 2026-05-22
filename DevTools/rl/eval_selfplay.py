import argparse
import json
from pathlib import Path

from env import EnvConfig, GrandArchiveSelfPlayEnv
from policy import TabularMaskedCategoricalPolicy, state_key_from_observation


def read_deck_text(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def run_match(env: GrandArchiveSelfPlayEnv, policy: TabularMaskedCategoricalPolicy, deck_text: str, seed: int, game_name: str):
    obs, mask, _ = env.reset(deck_text=deck_text, seed=seed, game_name=game_name)
    done = False
    reward = 0.0
    info = {}
    while not done:
        if not any(mask):
            return 0.0, {
                "winner": 0,
                "isTerminal": False,
                "timedOut": True,
                "stepCount": 0,
            }
        s_key = state_key_from_observation(obs)
        action_index = policy.select_action(s_key, mask, epsilon=0.0)
        obs, reward, done, mask, info = env.step(action_index)
    return reward, info


def main() -> None:
    parser = argparse.ArgumentParser(description="GrandArchiveSim deterministic self-play RL evaluator")
    parser.add_argument("--root", default="GrandArchiveSim")
    parser.add_argument("--deck-file", required=True)
    parser.add_argument("--checkpoint", required=True)
    parser.add_argument("--matches", type=int, default=50)
    parser.add_argument("--seed", type=int, default=123)
    parser.add_argument("--max-steps", type=int, default=400)
    parser.add_argument("--max-turns", type=int, default=100)
    args = parser.parse_args()

    deck_text = read_deck_text(Path(args.deck_file))
    policy = TabularMaskedCategoricalPolicy.load(Path(args.checkpoint))
    env = GrandArchiveSelfPlayEnv(EnvConfig(root=args.root, max_steps=args.max_steps, max_turns=args.max_turns))

    wins_p1 = 0
    wins_p2 = 0
    draws = 0
    timeouts = 0
    total_steps = 0

    for i in range(args.matches):
        match_seed = args.seed + i
        reward, info = run_match(env, policy, deck_text, match_seed, f"rl_eval_{match_seed}")
        winner = int(info.get("winner", 0))
        if winner == 1:
            wins_p1 += 1
        elif winner == 2:
            wins_p2 += 1
        else:
            draws += 1
        if bool(info.get("timedOut", False)):
            timeouts += 1
        total_steps += int(info.get("stepCount", 0))

    result = {
        "success": True,
        "matches": args.matches,
        "seedStart": args.seed,
        "winsAsP1": wins_p1,
        "winsAsP2": wins_p2,
        "draws": draws,
        "timeouts": timeouts,
        "meanSteps": (total_steps / args.matches) if args.matches > 0 else 0.0,
        "winRateP1": (wins_p1 / args.matches) if args.matches > 0 else 0.0,
        "winRateP2": (wins_p2 / args.matches) if args.matches > 0 else 0.0,
    }
    print(json.dumps(result, indent=2))


if __name__ == "__main__":
    main()
