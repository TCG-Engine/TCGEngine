import json
import math
import random
from pathlib import Path
from typing import Dict, List, Sequence, Tuple


def state_key_from_observation(obs: Dict) -> str:
    return json.dumps(obs.get("scalars", {}), sort_keys=True, separators=(",", ":"))


class TabularMaskedCategoricalPolicy:
    def __init__(self, max_actions: int = 256, temperature: float = 1.0, learning_rate: float = 0.05):
        self.max_actions = max_actions
        self.temperature = max(1e-6, temperature)
        self.learning_rate = learning_rate
        self.logits: Dict[str, List[float]] = {}

    def _ensure_state(self, state_key: str) -> List[float]:
        if state_key not in self.logits:
            self.logits[state_key] = [0.0] * self.max_actions
        return self.logits[state_key]

    def action_probabilities(self, state_key: str, legal_indices: Sequence[int]) -> List[Tuple[int, float]]:
        if not legal_indices:
            return []
        state_logits = self._ensure_state(state_key)
        vals = []
        max_logit = -1e30
        for idx in legal_indices:
            l = state_logits[idx] / self.temperature
            vals.append((idx, l))
            if l > max_logit:
                max_logit = l
        exp_vals = [(idx, math.exp(l - max_logit)) for idx, l in vals]
        denom = sum(v for _, v in exp_vals)
        return [(idx, (v / denom if denom > 0 else 1.0 / len(exp_vals))) for idx, v in exp_vals]

    def select_action(self, state_key: str, legal_mask: Sequence[int], epsilon: float = 0.0) -> int:
        legal_indices = [i for i, m in enumerate(legal_mask[: self.max_actions]) if m]
        if not legal_indices:
            raise RuntimeError("No legal actions available for policy selection.")
        if random.random() < epsilon:
            return random.choice(legal_indices)
        probs = self.action_probabilities(state_key, legal_indices)
        r = random.random()
        acc = 0.0
        for idx, p in probs:
            acc += p
            if r <= acc:
                return idx
        return probs[-1][0]

    def update_episode(self, episode_steps: List[Dict], terminal_reward: float) -> None:
        for step in episode_steps:
            s_key = step["state_key"]
            action_idx = int(step["action_index"])
            legal_indices = step["legal_indices"]
            if action_idx >= self.max_actions:
                continue
            probs = self.action_probabilities(s_key, legal_indices)
            state_logits = self._ensure_state(s_key)
            for idx, p in probs:
                grad = (1.0 if idx == action_idx else 0.0) - p
                state_logits[idx] += self.learning_rate * terminal_reward * grad

    def save(self, path: Path) -> None:
        payload = {
            "max_actions": self.max_actions,
            "temperature": self.temperature,
            "learning_rate": self.learning_rate,
            "logits": self.logits,
        }
        path.parent.mkdir(parents=True, exist_ok=True)
        path.write_text(json.dumps(payload, indent=2), encoding="utf-8")

    @classmethod
    def load(cls, path: Path) -> "TabularMaskedCategoricalPolicy":
        payload = json.loads(path.read_text(encoding="utf-8"))
        obj = cls(
            max_actions=int(payload["max_actions"]),
            temperature=float(payload["temperature"]),
            learning_rate=float(payload["learning_rate"]),
        )
        obj.logits = {k: [float(x) for x in v] for k, v in payload.get("logits", {}).items()}
        return obj
