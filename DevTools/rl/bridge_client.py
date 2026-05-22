import base64
import json
import subprocess
from pathlib import Path
from typing import Any, Dict


REPO_ROOT = Path(__file__).resolve().parents[2]
BRIDGE_PATH = REPO_ROOT / "DevTools" / "TestAutomationBridge.php"


class BridgeClient:
    def __init__(self, root: str = "GrandArchiveSim"):
        self.root = root

    def _run(self, command: str, **kwargs: str) -> Dict[str, Any]:
        args = ["php", str(BRIDGE_PATH), f"--command={command}", f"--root={self.root}"]
        for key, value in kwargs.items():
            args.append(f"--{key}={value}")
        proc = subprocess.run(
            args,
            cwd=str(REPO_ROOT),
            capture_output=True,
            text=True,
            check=False,
        )
        stdout = (proc.stdout or "").strip()
        stdout = stdout.lstrip("\ufeff")
        if stdout and not stdout.startswith("{"):
            first_obj = stdout.find("{")
            if first_obj >= 0:
                stdout = stdout[first_obj:]
        if not stdout:
            raise RuntimeError(f"Bridge returned empty output. stderr={proc.stderr.strip()}")
        try:
            payload = json.loads(stdout)
        except Exception as exc:
            raise RuntimeError(f"Bridge returned invalid JSON: {stdout}") from exc
        return payload

    @staticmethod
    def _b64(text: str) -> str:
        return base64.b64encode(text.encode("utf-8")).decode("ascii")

    def start_selfplay_game(self, game_name: str, seed: int, deck_text_p1: str, deck_text_p2: str = "") -> Dict[str, Any]:
        return self._run(
            "start-selfplay-game",
            gameName=game_name,
            seed=str(seed),
            deckTextP1=self._b64(deck_text_p1),
            deckTextP2=self._b64(deck_text_p2 or deck_text_p1),
        )

    def enumerate_legal_actions(self, game_name: str) -> Dict[str, Any]:
        return self._run("enumerate-legal-actions", gameName=game_name)

    def apply_engine_action(self, game_name: str, action: Dict[str, Any]) -> Dict[str, Any]:
        encoded = base64.b64encode(json.dumps(action).encode("utf-8")).decode("ascii")
        return self._run("apply-engine-action", gameName=game_name, action=encoded)

    def step_selfplay_game(self, game_name: str, action: Dict[str, Any]) -> Dict[str, Any]:
        encoded = base64.b64encode(json.dumps(action).encode("utf-8")).decode("ascii")
        return self._run("step-selfplay-game", gameName=game_name, action=encoded)

    def get_game_snapshot(self, game_name: str, view: str = "summary") -> Dict[str, Any]:
        return self._run("get-game-snapshot", gameName=game_name, view=view)
