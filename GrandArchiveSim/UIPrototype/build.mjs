import { build } from 'esbuild';
import { statSync } from 'node:fs';
import { gzipSync } from 'node:zlib';
import { readFileSync } from 'node:fs';
await build({ entryPoints: ['src/main.jsx'], bundle: true, minify: true, outdir: 'dist', entryNames: 'app', define: { 'process.env.NODE_ENV': '"production"' }, target: ['es2020'], metafile: true });
console.log(`Production JS: ${statSync('dist/app.js').size} bytes; gzip: ${gzipSync(readFileSync('dist/app.js')).length} bytes`);
