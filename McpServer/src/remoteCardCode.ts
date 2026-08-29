export interface RemoteCardCodeConfig {
  url: string;
  workspace: string;
  token: string;
}

let parsedConfig: Record<string, any> | null = null;

function allConfig(): Record<string, any> {
  if (parsedConfig) return parsedConfig;
  const raw = String(process.env.CARD_CODE_REMOTE_CONFIG || '').trim();
  if (!raw) return parsedConfig = {};
  try {
    const decoded = JSON.parse(raw);
    return parsedConfig = decoded && typeof decoded === 'object' ? decoded : {};
  } catch {
    throw new Error('CARD_CODE_REMOTE_CONFIG must be a valid JSON object');
  }
}

export function remoteCardCodeRoots(): string[] { return Object.keys(allConfig()); }

export function getRemoteCardCodeConfig(root: string): RemoteCardCodeConfig | null {
  const entry = allConfig()[root];
  if (!entry || typeof entry !== 'object') return null;
  const url = String(entry.url || '').replace(/\/+$/, '');
  const workspace = String(entry.workspace || root).trim();
  const tokenEnv = String(entry.tokenEnv || 'CARD_CODE_REMOTE_TOKEN').trim();
  const token = String(process.env[tokenEnv] || '').trim();
  if (!url || !workspace || !token) throw new Error(`Remote Card Code backend for ${root} is missing url, workspace, or ${tokenEnv}`);
  if (!/^https:\/\//i.test(url) && !/^http:\/\/(localhost|127\.0\.0\.1|\[::1\])(?::\d+)?\//i.test(url)) {
    throw new Error(`Remote Card Code URL for ${root} must use HTTPS (HTTP is allowed only for loopback)`);
  }
  return { url, workspace, token };
}

export async function remoteCardCodeRequest(root: string, action: string, method: 'GET' | 'POST' = 'GET', body: Record<string, unknown> = {}): Promise<any> {
  const config = getRemoteCardCodeConfig(root);
  if (!config) throw new Error(`${root} does not use a remote Card Code backend`);
  const url = new URL(config.url);
  url.searchParams.set('action', action);
  const options: RequestInit = { method, headers: { Authorization: `Bearer ${config.token}`, Accept: 'application/json' } };
  if (method === 'GET') {
    url.searchParams.set('root', config.workspace);
    for (const [key, value] of Object.entries(body)) if (value !== undefined && value !== null) url.searchParams.set(key, String(value));
  } else {
    (options.headers as Record<string, string>)['Content-Type'] = 'application/json';
    options.body = JSON.stringify({ ...body, root: config.workspace });
  }
  const response = await fetch(url, options);
  const payload = await response.json().catch(() => ({})) as any;
  if (!response.ok || !payload.success) {
    const error: any = new Error(payload.error || `Remote Card Code service returned HTTP ${response.status}`);
    error.status = response.status;
    error.conflict = payload.conflict;
    throw error;
  }
  return payload;
}
