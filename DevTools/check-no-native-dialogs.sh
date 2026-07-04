#!/usr/bin/env bash
# Fails if a native alert()/confirm()/prompt() appears outside the allowlist.
# Allowlisted: NextTurn.php (dev/test fixture builder), the primitive itself, node_modules, docs, .git.
set -uo pipefail
cd "$(dirname "$0")/.."
ALLOW='NextTurn\.php|Core/StyledDialog\.js|/node_modules/|docs/superpowers/|\.git/'
# Bare or window.-qualified calls to the three natives. The optional `window\.` lets us catch
# window.confirm(...) while the leading non-identifier class still rejects foo.confirm(...).
hits=$(grep -rnE '(^|[^.[:alnum:]_])(window\.)?(alert|confirm|prompt)\(' \
  --include='*.js' --include='*.php' --include='*.html' . 2>/dev/null \
  | grep -vE "$ALLOW" \
  | grep -vE 'passConfirm|customFilter|legalFilter|//' ) || true
if [ -n "$hits" ]; then
  echo "❌ Native browser dialogs found (use StyledConfirm/StyledPrompt/StyledAlert/Toast):"
  echo "$hits"
  echo ""
  echo "Count: $(echo "$hits" | grep -c .)"
  exit 1
fi
echo "✅ No native alert/confirm/prompt outside the allowlist."
