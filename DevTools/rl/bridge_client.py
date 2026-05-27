import base64
import json
import subprocess
import threading
import atexit
from pathlib import Path
from typing import Any, Dict


REPO_ROOT = Path(__file__).resolve().parents[2]
BRIDGE_PATH = REPO_ROOT / "DevTools" / "TestAutomationBridge.php"


class _BridgeDaemonSession:
    def __init__(self) -> None:
        self._proc: subprocess.Popen[str] | None = None
        self._lock = threading.Lock()

    def _ensure_started(self) -> None:
        if self._proc is not None and self._proc.poll() is None:
            return
        self._proc = subprocess.Popen(
            ["php", str(BRIDGE_PATH), "--daemon=1"],
            cwd=str(REPO_ROOT),
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.DEVNULL,
            text=True,
            bufsize=1,
        )

    def close(self) -> None:
        if self._proc is None:
            return
        try:
            if self._proc.stdin:
                self._proc.stdin.close()
        except Exception:
            pass
        try:
            self._proc.terminate()
        except Exception:
            pass
        self._proc = None

    def request(self, command: str, root: str, args: Dict[str, str]) -> Dict[str, Any]:
        with self._lock:
            self._ensure_started()
            assert self._proc is not None
            assert self._proc.stdin is not None
            assert self._proc.stdout is not None
            payload = {"command": command, "root": root, "args": args}
            self._proc.stdin.write(json.dumps(payload) + "\n")
            self._proc.stdin.flush()
            line = self._proc.stdout.readline()
            if not line:
                raise RuntimeError("Bridge daemon returned empty response.")
            return json.loads(line)


_DAEMON_SESSION = _BridgeDaemonSession()
atexit.register(_DAEMON_SESSION.close)


class BridgeClient:
    def __init__(self, root: str = "GrandArchiveSim", use_daemon: bool = True):
        self.root = root
        self.use_daemon = use_daemon

    def _run(self, command: str, **kwargs: str) -> Dict[str, Any]:
        if self.use_daemon:
            try:
                response = _DAEMON_SESSION.request(command, self.root, kwargs)
                if (
                    isinstance(response, dict)
                    and response.get("success") is False
                    and str(response.get("message", "")) == "Bridge daemon request failed."
                ):
                    # Recover from daemon-side runtime drift by restarting daemon
                    # and replaying this request in one-shot mode.
                    _DAEMON_SESSION.close()
                    raise RuntimeError("bridge-daemon-request-failed")
                return response
            except Exception:
                # Fallback to one-shot subprocess mode for robustness.
                pass

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

    def start_selfplay_game(
        self, game_name: str, seed: int, deck_text_p1: str, deck_text_p2: str = "", memory_only: bool | None = None
    ) -> Dict[str, Any]:
        memory_arg = "auto" if memory_only is None else ("1" if memory_only else "0")
        return self._run(
            "start-selfplay-game",
            gameName=game_name,
            seed=str(seed),
            deckTextP1=self._b64(deck_text_p1),
            deckTextP2=self._b64(deck_text_p2 or deck_text_p1),
            memoryOnly=memory_arg,
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
