from dataclasses import dataclass
import time
from typing import Any, Dict, List, Optional, Tuple

from bridge_client import BridgeClient


@dataclass
class EnvConfig:
    root: str = "GrandArchiveSim"
    max_steps: int = 1000
    max_turns: int = 100
    per_step_penalty: float = 0.0
    memory_only: Optional[bool] = None


class GrandArchiveSelfPlayEnv:
    def __init__(self, config: EnvConfig):
        self.config = config
        self.bridge = BridgeClient(root=config.root)
        self.game_name = ""
        self.seed = 0
        self.step_count = 0
        self.last_legal_actions: List[Dict[str, Any]] = []
        self.last_legal_kind: str = ""
        self.last_info: Dict[str, Any] = {}

    def _observation_from_snapshot(self, snapshot: Dict[str, Any]) -> Dict[str, Any]:
        zones = snapshot.get("zones", {})
        p1 = snapshot.get("players", {}).get("player1", {})
        p2 = snapshot.get("players", {}).get("player2", {})
        c1 = p1.get("champion", {})
        c2 = p2.get("champion", {})
        scalars = {
            "activePlayer": int(snapshot.get("activePlayer", 0)),
            "turnPlayer": int(snapshot.get("turnPlayer", 0)),
            "turnNumber": int(snapshot.get("turnNumber", 0)),
            "phase": str(snapshot.get("phase", "")),
            "myHandCount": int(zones.get("myHandCount", 0)),
            "theirHandCount": int(zones.get("theirHandCount", 0)),
            "myDeckCount": int(zones.get("myDeckCount", 0)),
            "theirDeckCount": int(zones.get("theirDeckCount", 0)),
            "myMemoryCount": int(zones.get("myMemoryCount", 0)),
            "theirMemoryCount": int(zones.get("theirMemoryCount", 0)),
            "myMaterialCount": int(zones.get("myMaterialCount", 0)),
            "theirMaterialCount": int(zones.get("theirMaterialCount", 0)),
            "p1ChampionRemainingLife": int(c1.get("remainingLife", 0)),
            "p2ChampionRemainingLife": int(c2.get("remainingLife", 0)),
            "p1ChampionDamage": int(c1.get("damage", 0)),
            "p2ChampionDamage": int(c2.get("damage", 0)),
            "p1DQCount": int(p1.get("decisionQueue", {}).get("count", 0)),
            "p2DQCount": int(p2.get("decisionQueue", {}).get("count", 0)),
        }
        return {"scalars": scalars, "snapshot": snapshot}

    def _get_action_mask(self, legal_payload: Dict[str, Any]) -> List[int]:
        actions = legal_payload.get("actions", [])
        self.last_legal_actions = actions if isinstance(actions, list) else []
        self.last_legal_kind = str(legal_payload.get("kind", ""))
        return [1 for _ in self.last_legal_actions]

    def _terminal_from_snapshot(self, snapshot: Dict[str, Any]) -> Tuple[bool, int]:
        terminal = snapshot.get("terminal", {})
        is_terminal = bool(terminal.get("isTerminal", False))
        winner = int(terminal.get("winner", 0))
        return is_terminal, winner

    def reset(self, deck_text: str, seed: int, game_name: str) -> Tuple[Dict[str, Any], List[int], Dict[str, Any]]:
        self.seed = seed
        self.game_name = game_name
        self.step_count = 0
        start = self.bridge.start_selfplay_game(
            game_name=game_name,
            seed=seed,
            deck_text_p1=deck_text,
            memory_only=self.config.memory_only,
        )
        if not start.get("success", False):
            raise RuntimeError(f"start-selfplay-game failed: {start}")
        legal = start.get("legalActions", {})
        snapshot = start.get("snapshot", {}) if isinstance(start.get("snapshot", {}), dict) else {}
        if not snapshot:
            snapshot = self.bridge.get_game_snapshot(self.game_name, "summary")
        obs = self._observation_from_snapshot(snapshot)
        mask = self._get_action_mask(legal)
        info = {
            "seed": seed,
            "gameName": self.game_name,
            "memoryOnlyResolved": start.get("memoryOnlyResolved", None),
            "deckParseSummary": start.get("deckParseSummary", []),
            "terminal": snapshot.get("terminal", {}),
            "gamestateHash": snapshot.get("gamestateHash", ""),
        }
        self.last_info = info
        return obs, mask, info

    def step(self, action_index: int) -> Tuple[Dict[str, Any], float, bool, List[int], Dict[str, Any]]:
        if action_index < 0 or action_index >= len(self.last_legal_actions):
            raise IndexError(f"Invalid action_index {action_index} for {len(self.last_legal_actions)} legal actions.")

        pre_hash = str(self.last_info.get("gamestateHash", ""))
        action = self.last_legal_actions[action_index]
        chosen_action = dict(action)
        t0 = time.perf_counter()
        step_payload = self.bridge.step_selfplay_game(self.game_name, action)
        round_trip_ms = int((time.perf_counter() - t0) * 1000)
        if not step_payload.get("success", False):
            raise RuntimeError(f"step-selfplay-game failed: {step_payload}")
        result = step_payload.get("applyResult", {})
        if not isinstance(result, dict) or not result.get("success", False):
            raise RuntimeError(f"apply-engine-action failed: {result}")

        self.step_count += 1
        snapshot = step_payload.get("snapshot", {})
        if not isinstance(snapshot, dict):
            snapshot = self.bridge.get_game_snapshot(self.game_name, "summary")
        post_hash = str(snapshot.get("gamestateHash", ""))
        state_changed = pre_hash != post_hash
        obs = self._observation_from_snapshot(snapshot)
        is_terminal, winner = self._terminal_from_snapshot(snapshot)
        timed_out = False

        current_turn = int(snapshot.get("turnNumber", 0))
        if self.step_count >= self.config.max_steps:
            timed_out = True
        if current_turn > self.config.max_turns:
            timed_out = True

        done = is_terminal or timed_out
        reward = self.config.per_step_penalty
        if done:
            if is_terminal:
                if winner == 1:
                    reward = 1.0
                elif winner == 2:
                    reward = -1.0
                else:
                    reward = 0.0
            else:
                reward = 0.0

        legal = step_payload.get("legalActions", {"actions": []}) if not done else {"actions": []}
        if not isinstance(legal, dict):
            legal = {"actions": []}
        mask = self._get_action_mask(legal)
        if not done and len(mask) == 0:
            done = True
            timed_out = True
            reward = 0.0
        info = {
            "winner": winner,
            "isTerminal": is_terminal,
            "timedOut": timed_out,
            "stepCount": self.step_count,
            "gamestateHash": snapshot.get("gamestateHash", ""),
            "legalKind": legal.get("kind", ""),
            "chosenAction": chosen_action,
            "stateChanged": state_changed,
            "preHash": pre_hash,
            "postHash": post_hash,
            "flashMessage": str(snapshot.get("flashMessage", "")),
            "timingsMs": step_payload.get("timingsMs", {}),
            "roundTripMs": round_trip_ms,
        }
        self.last_info = info
        return obs, reward, done, mask, info
