import argparse
import csv
import json
import random
import time
from collections import Counter
from collections import defaultdict
from datetime import datetime, timedelta
from pathlib import Path
from typing import Dict, List

from env import EnvConfig, GrandArchiveSelfPlayEnv
from policy import TabularMaskedCategoricalPolicy, state_key_from_observation


def read_deck_text(path: Path) -> str:
    return path.read_text(encoding="utf-8")


def _candidate_indices(mask: List[int], actions: List[Dict], no_op_keys: set, state_key: str) -> List[int]:
    legal = [i for i, m in enumerate(mask) if m]
    if not legal:
        return []

    # Reject actions known to be no-op for this exact state.
    filtered = [i for i in legal if f"{state_key}|{int(actions[i].get('mode', -1))}|{str(actions[i].get('cardID', ''))}" not in no_op_keys]
    # Keep all currently legal actions (including PASS) choosable.
    return filtered


def _action_signature(action: Dict) -> str:
    mode = int(action.get("mode", -1))
    card_id = str(action.get("cardID", ""))
    button_input = str(action.get("buttonInput", ""))
    input_text = str(action.get("inputText", ""))
    chk_input = action.get("chkInput", [])
    if isinstance(chk_input, list):
        chk_key = ",".join(str(v) for v in chk_input)
    else:
        chk_key = str(chk_input)
    return f"mode={mode}|card={card_id}|button={button_input}|input={input_text}|chk={chk_key}"


def _steps_for_player(episode_steps: List[Dict], player: int) -> List[Dict]:
    return [step for step in episode_steps if int(step.get("turn_player", 0)) == int(player)]


def _build_stuck_diagnostics(step_trace: List[Dict], window: int = 200) -> Dict:
    if not step_trace:
        return {"traceWindow": 0, "topActions": [], "topTransitions": [], "stateChangeRate": 0.0}
    tail = step_trace[-max(1, int(window)) :]
    action_counts = Counter(item.get("actionSignature", "") for item in tail)
    transition_counts = Counter(
        f"{item.get('preHash', '')}->{item.get('postHash', '')}" for item in tail if item.get("preHash", "") or item.get("postHash", "")
    )
    changed = sum(1 for item in tail if bool(item.get("stateChanged", True)))
    return {
        "traceWindow": len(tail),
        "stateChangeRate": (changed / len(tail)) if tail else 0.0,
        "topActions": [{"signature": sig, "count": cnt} for sig, cnt in action_counts.most_common(10)],
        "topTransitions": [{"transition": sig, "count": cnt} for sig, cnt in transition_counts.most_common(10)],
        "tailTrace": tail,
    }


def main() -> None:
    parser = argparse.ArgumentParser(description="TCGEngine deterministic self-play RL MVP trainer")
    parser.add_argument("--root", default="GrandArchiveSim")
    parser.add_argument("--deck-file", required=True)
    parser.add_argument("--episodes", type=int, default=100)
    parser.add_argument("--seed", type=int, default=123)
    parser.add_argument("--max-steps", type=int, default=1000)
    parser.add_argument("--max-turns", type=int, default=100)
    parser.add_argument("--max-actions", type=int, default=256)
    parser.add_argument("--learning-rate", type=float, default=0.05)
    parser.add_argument("--temperature", type=float, default=1.0)
    parser.add_argument("--epsilon", type=float, default=0.05)
    parser.add_argument("--timeout-reward", type=float, default=-0.25)
    parser.add_argument("--checkpoint-every", type=int, default=25)
    parser.add_argument("--log-every", type=int, default=25)
    parser.add_argument("--stuck-debug-window", type=int, default=200)
    parser.add_argument("--memory-only", dest="memory_only", action="store_const", const=True, default=None)
    parser.add_argument("--disk-games", dest="memory_only", action="store_const", const=False)
    args = parser.parse_args()

    random.seed(args.seed)
    deck_text = read_deck_text(Path(args.deck_file))

    run_id = time.strftime("%Y%m%d-%H%M%S")
    run_dir = Path(__file__).resolve().parent / "artifacts" / "runs" / run_id
    ckpt_dir = run_dir / "checkpoints"
    replay_dir = run_dir / "replays"
    run_dir.mkdir(parents=True, exist_ok=True)
    ckpt_dir.mkdir(parents=True, exist_ok=True)
    replay_dir.mkdir(parents=True, exist_ok=True)

    env = GrandArchiveSelfPlayEnv(
        EnvConfig(
            root=args.root,
            max_steps=args.max_steps,
            max_turns=args.max_turns,
            per_step_penalty=0.0,
            memory_only=args.memory_only,
        )
    )
    policy = TabularMaskedCategoricalPolicy(
        max_actions=args.max_actions, temperature=args.temperature, learning_rate=args.learning_rate
    )
    frozen_pool: List[TabularMaskedCategoricalPolicy] = []

    metrics_csv = run_dir / "metrics.csv"
    timing_csv = run_dir / "timing_metrics.csv"
    step_timing_csv = run_dir / "step_timing_metrics.csv"
    run_timing = {"applyMs": 0, "snapshotMs": 0, "enumerateMs": 0, "bridgeTotalMs": 0, "steps": 0}
    action_timing: Dict[str, Dict[str, float]] = defaultdict(lambda: {"count": 0, "totalMs": 0.0})
    train_start = time.time()
    completed_steps = 0
    with metrics_csv.open("w", newline="", encoding="utf-8") as f:
        tf = timing_csv.open("w", newline="", encoding="utf-8")
        sf = step_timing_csv.open("w", newline="", encoding="utf-8")
        timing_writer = csv.DictWriter(
            tf,
            fieldnames=["episode", "seed", "steps", "applyMs", "snapshotMs", "enumerateMs", "bridgeTotalMs", "bridgeMsPerStep"],
        )
        timing_writer.writeheader()
        step_timing_writer = csv.DictWriter(
            sf,
            fieldnames=[
                "episode",
                "seed",
                "step",
                "mode",
                "cardID",
                "playerID",
                "roundTripMs",
                "applyMs",
                "snapshotMs",
                "enumerateMs",
                "bridgeTotalMs",
            ],
        )
        step_timing_writer.writeheader()
        writer = csv.DictWriter(
            f,
            fieldnames=["episode", "seed", "winner", "reward", "steps", "timedOut", "elapsedMs", "frozenPoolSize"],
        )
        writer.writeheader()

        for ep in range(args.episodes):
            ep_seed = args.seed + ep
            game_name = f"rl_train_{run_id}_{ep + 1:04d}_{ep_seed}"
            obs, mask, reset_info = env.reset(deck_text=deck_text, seed=ep_seed, game_name=game_name)
            done = False
            episode_steps: List[Dict] = []
            replay_actions: List[Dict] = []
            step_trace: List[Dict] = []
            no_op_action_keys = set()
            start = time.time()
            ep_timing = {"applyMs": 0, "snapshotMs": 0, "enumerateMs": 0, "bridgeTotalMs": 0}

            # Simple opponent pool behavior: when it's player 2's turn, sometimes use a frozen checkpoint policy.
            opponent = random.choice(frozen_pool) if frozen_pool and (ep % 2 == 1) else policy

            while not done:
                if not any(mask):
                    done = True
                    reward = 0.0
                    info = {
                        "winner": 0,
                        "isTerminal": False,
                        "timedOut": True,
                        "stepCount": len(replay_actions),
                        "gamestateHash": "",
                        "legalKind": "no-legal-actions",
                    }
                    break
                turn_player = int(obs["scalars"].get("turnPlayer", 1))
                acting_policy = policy if turn_player == 1 else opponent
                s_key = state_key_from_observation(obs)
                bounded_mask = list(mask[: args.max_actions])
                legal_indices = _candidate_indices(
                    bounded_mask,
                    env.last_legal_actions,
                    no_op_action_keys,
                    s_key,
                )
                if not legal_indices:
                    done = True
                    reward = 0.0
                    info = {
                        "winner": 0,
                        "isTerminal": False,
                        "timedOut": True,
                        "stepCount": len(replay_actions),
                        "gamestateHash": "",
                        "legalKind": "no-candidate-actions",
                    }
                    break
                filtered_mask = [1 if i in legal_indices else 0 for i in range(len(bounded_mask))]
                action_index = acting_policy.select_action(s_key, filtered_mask, epsilon=args.epsilon)

                episode_steps.append(
                    {
                        "state_key": s_key,
                        "action_index": action_index,
                        "legal_indices": legal_indices,
                        "turn_player": turn_player,
                    }
                )
                replay_actions.append(
                    {
                        "step": len(replay_actions) + 1,
                        "turnPlayer": turn_player,
                        "actionIndex": action_index,
                        "legalCount": len(legal_indices),
                        "action": dict(env.last_legal_actions[action_index]) if action_index < len(env.last_legal_actions) else {},
                    }
                )

                try:
                    obs, reward, done, mask, info = env.step(action_index)
                except Exception as exc:
                    done = True
                    reward = 0.0
                    info = {
                        "winner": 0,
                        "isTerminal": False,
                        "timedOut": True,
                        "stepCount": len(replay_actions),
                        "gamestateHash": "",
                        "legalKind": "engine-error",
                        "error": str(exc),
                    }
                timings = info.get("timingsMs", {})
                chosen = info.get("chosenAction", {})
                action_signature = _action_signature(chosen)
                mode = int(chosen.get("mode", -1))
                card_id = str(chosen.get("cardID", ""))
                player_id = int(chosen.get("playerID", 0))
                round_trip_ms = int(info.get("roundTripMs", 0) or 0)
                apply_ms = int(timings.get("apply", 0) or 0)
                snap_ms = int(timings.get("snapshot", 0) or 0)
                enum_ms = int(timings.get("enumerate", 0) or 0)
                total_ms = int(timings.get("total", 0) or 0)
                ep_timing["applyMs"] += int(timings.get("apply", 0) or 0)
                ep_timing["snapshotMs"] += int(timings.get("snapshot", 0) or 0)
                ep_timing["enumerateMs"] += int(timings.get("enumerate", 0) or 0)
                ep_timing["bridgeTotalMs"] += int(timings.get("total", 0) or 0)
                step_timing_writer.writerow(
                    {
                        "episode": ep + 1,
                        "seed": ep_seed,
                        "step": info.get("stepCount", 0),
                        "mode": mode,
                        "cardID": card_id,
                        "playerID": player_id,
                        "roundTripMs": round_trip_ms,
                        "applyMs": apply_ms,
                        "snapshotMs": snap_ms,
                        "enumerateMs": enum_ms,
                        "bridgeTotalMs": total_ms,
                    }
                )
                key = f"{mode}|{card_id}"
                action_timing[key]["count"] += 1
                action_timing[key]["totalMs"] += float(total_ms)
                if not bool(info.get("stateChanged", True)) and not done:
                    action = info.get("chosenAction", {})
                    action_mode = int(action.get("mode", -1))
                    action_card = str(action.get("cardID", ""))
                    no_op_action_keys.add(f"{s_key}|{action_mode}|{action_card}")
                step_trace.append(
                    {
                        "step": int(info.get("stepCount", 0) or 0),
                        "turnPlayer": turn_player,
                        "legalCount": len(legal_indices),
                        "actionSignature": action_signature,
                        "stateChanged": bool(info.get("stateChanged", True)),
                        "preHash": str(info.get("preHash", "")),
                        "postHash": str(info.get("postHash", "")),
                        "gamestateHash": str(info.get("gamestateHash", "")),
                    }
                )

            terminal_reward = float(reward)
            if bool(info.get("timedOut", False)) and not bool(info.get("isTerminal", False)):
                terminal_reward = float(args.timeout_reward)
            policy.update_episode(_steps_for_player(episode_steps, 1), terminal_reward)
            if opponent is policy:
                p2_reward = float(args.timeout_reward) if bool(info.get("timedOut", False)) and not bool(info.get("isTerminal", False)) else -terminal_reward
                policy.update_episode(_steps_for_player(episode_steps, 2), p2_reward)
            elapsed_ms = int((time.time() - start) * 1000)

            writer.writerow(
                {
                    "episode": ep + 1,
                    "seed": ep_seed,
                    "winner": info.get("winner", 0),
                    "reward": terminal_reward,
                    "steps": info.get("stepCount", 0),
                    "timedOut": bool(info.get("timedOut", False)),
                    "elapsedMs": elapsed_ms,
                    "frozenPoolSize": len(frozen_pool),
                }
            )
            f.flush()
            steps = int(info.get("stepCount", 0) or 0)
            bridge_ms_per_step = (ep_timing["bridgeTotalMs"] / steps) if steps > 0 else 0.0
            timing_writer.writerow(
                {
                    "episode": ep + 1,
                    "seed": ep_seed,
                    "steps": steps,
                    "applyMs": ep_timing["applyMs"],
                    "snapshotMs": ep_timing["snapshotMs"],
                    "enumerateMs": ep_timing["enumerateMs"],
                    "bridgeTotalMs": ep_timing["bridgeTotalMs"],
                    "bridgeMsPerStep": f"{bridge_ms_per_step:.3f}",
                }
            )
            tf.flush()
            sf.flush()
            completed_steps += steps
            run_timing["applyMs"] += ep_timing["applyMs"]
            run_timing["snapshotMs"] += ep_timing["snapshotMs"]
            run_timing["enumerateMs"] += ep_timing["enumerateMs"]
            run_timing["bridgeTotalMs"] += ep_timing["bridgeTotalMs"]
            run_timing["steps"] += steps

            replay_payload = {
                "episode": ep + 1,
                "seed": ep_seed,
                "gameName": reset_info.get("gameName"),
                "memoryOnlyResolved": reset_info.get("memoryOnlyResolved", None),
                "initialGamestateHash": reset_info.get("gamestateHash", ""),
                "initialGamestateText": reset_info.get("initialGamestateText", ""),
                "deckParseSummary": reset_info.get("deckParseSummary", []),
                "result": info,
                "actions": replay_actions,
            }
            if bool(info.get("timedOut", False)):
                replay_payload["stuckDiagnostics"] = _build_stuck_diagnostics(
                    step_trace, window=args.stuck_debug_window
                )
            (replay_dir / f"episode_{ep + 1:04d}.json").write_text(
                json.dumps(replay_payload, indent=2), encoding="utf-8"
            )

            if (ep + 1) % args.checkpoint_every == 0 or (ep + 1) == args.episodes:
                ckpt_path = ckpt_dir / f"episode_{ep + 1:04d}.json"
                policy.save(ckpt_path)
                policy.save(ckpt_dir / "latest.json")
                frozen_pool.append(TabularMaskedCategoricalPolicy.load(ckpt_path))
                if len(frozen_pool) > 5:
                    frozen_pool.pop(0)

            if args.log_every > 0 and (((ep + 1) % args.log_every == 0) or (ep + 1 == args.episodes)):
                elapsed_s = max(1e-9, time.time() - train_start)
                eps_done = ep + 1
                eps_per_s = eps_done / elapsed_s
                steps_per_s = completed_steps / elapsed_s
                eps_remaining = max(0, args.episodes - eps_done)
                eta_s = (eps_remaining / eps_per_s) if eps_per_s > 0 else float("inf")
                eta_text = "unknown"
                if eta_s != float("inf"):
                    eta_dt = datetime.now() + timedelta(seconds=eta_s)
                    eta_text = eta_dt.strftime("%Y-%m-%d %H:%M:%S")
                pct = (100.0 * eps_done / args.episodes) if args.episodes > 0 else 100.0
                print(
                    f"[progress] ep {eps_done}/{args.episodes} ({pct:.1f}%) | "
                    f"elapsed {elapsed_s:.1f}s | eps/s {eps_per_s:.3f} | steps/s {steps_per_s:.1f} | "
                    f"avgSteps/ep {(completed_steps / eps_done):.1f} | ETA {eta_text}"
                )

        tf.close()
        sf.close()

    run_config = {
        "root": args.root,
        "deckFile": args.deck_file,
        "episodes": args.episodes,
        "seed": args.seed,
        "maxSteps": args.max_steps,
        "maxTurns": args.max_turns,
        "maxActions": args.max_actions,
        "learningRate": args.learning_rate,
        "temperature": args.temperature,
        "epsilon": args.epsilon,
        "timeoutReward": args.timeout_reward,
        "memoryOnly": args.memory_only,
        "timingSummary": {
            "totalSteps": run_timing["steps"],
            "applyMs": run_timing["applyMs"],
            "snapshotMs": run_timing["snapshotMs"],
            "enumerateMs": run_timing["enumerateMs"],
            "bridgeTotalMs": run_timing["bridgeTotalMs"],
            "bridgeMsPerStep": (run_timing["bridgeTotalMs"] / run_timing["steps"]) if run_timing["steps"] > 0 else 0.0,
        },
        "topSlowActions": sorted(
            [
                {
                    "actionKey": key,
                    "count": int(stats["count"]),
                    "totalMs": round(float(stats["totalMs"]), 3),
                    "avgMs": round((float(stats["totalMs"]) / float(stats["count"])) if stats["count"] > 0 else 0.0, 3),
                }
                for key, stats in action_timing.items()
            ],
            key=lambda item: item["totalMs"],
            reverse=True,
        )[:25],
    }
    (run_dir / "run_config.json").write_text(json.dumps(run_config, indent=2), encoding="utf-8")
    print(json.dumps({"success": True, "runDir": str(run_dir), "latestCheckpoint": str(ckpt_dir / "latest.json")}, indent=2))


if __name__ == "__main__":
    main()
