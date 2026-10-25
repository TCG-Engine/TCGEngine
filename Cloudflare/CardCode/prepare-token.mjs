// Cloudflare account administrators apply the generated SQL through authenticated Wrangler.
// Secrets are emitted only into a protected local PHP file, never terminal output.
import { randomBytes, createHash } from 'node:crypto';
import { writeFileSync } from 'node:fs';
const [name, role, days, prefix] = process.argv.slice(2);
if (!name || !['reader', 'developer', 'maintainer'].includes(role) || !/^\d+$/.test(days || '') || Number(days) < 1 || Number(days) > 365 || !prefix) throw new Error('Usage: node prepare-token.mjs name reader|developer|maintainer 1..365 local/output-prefix');
const token = 'tcc_' + randomBytes(32).toString('base64url');
const hash = createHash('sha256').update(token).digest('hex');
const expires = new Date(Date.now() + Number(days) * 86400000).toISOString();
const quote = value => "'" + value.replaceAll("'", "''") + "'";
writeFileSync(prefix + '.php', '<?php http_response_code(404); exit; ?>\n' + JSON.stringify({ token, name, role, expiresAt: expires }) + '\n', { mode: 0o600, flag: 'wx' });
writeFileSync(prefix + '.sql', `INSERT INTO tokens(token_hash,token_name,root,role,expires_at) VALUES (${[hash,name,'GrandArchiveSim',role,expires].map(quote).join(',')});\n`, { mode: 0o600, flag: 'wx' });
console.log(`Prepared ${role} token; expires ${expires}. Secret saved to ${prefix}.php.`);
