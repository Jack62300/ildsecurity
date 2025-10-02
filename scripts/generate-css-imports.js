#!/usr/bin/env node
/* eslint-disable no-console */
const fs = require('fs/promises');
const path = require('path');

const ROOT = process.cwd();
const ASSETS_DIR = path.resolve(ROOT, 'assets');
const STYLES_DIR = path.resolve(ASSETS_DIR, 'styles');
const APP_CSS = path.resolve(ASSETS_DIR, 'app.css');

const START = '/* AUTO-GENERATED CSS IMPORTS START (do not edit) */';
const END   = '/* AUTO-GENERATED CSS IMPORTS END */';

const PRINT_IMPORTS = process.argv.includes('--print-imports');

async function walk(dir, acc = []) {
  const entries = await fs.readdir(dir, { withFileTypes: true });
  for (const e of entries) {
    const abs = path.join(dir, e.name);
    if (e.isDirectory()) {
      await walk(abs, acc);
    } else if (e.isFile() && e.name.endsWith('.css')) {
      acc.push(abs);
    }
  }
  return acc;
}

function toPosix(relPath) {
  return relPath.split(path.sep).join('/');
}

function buildImportLine(absCssPath) {
  // Import en relatif depuis "assets/app.css"
  const relFromAssets = path.relative(ASSETS_DIR, absCssPath); // styles/...
  return `@import "./${toPosix(relFromAssets)}";`;
}

function blockRegex() {
  const esc = s => s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
  return new RegExp(`${esc(START)}[\\s\\S]*?${esc(END)}`, 'm');
}

async function ensureAppCss() {
  try {
    await fs.access(APP_CSS);
  } catch {
    const boilerplate = `/* Main CSS entry for AssetMapper
 * Vos imports auto seront insérés entre les deux marqueurs ci-dessous.
 */
${START}
${END}
`;
    await fs.mkdir(path.dirname(APP_CSS), { recursive: true });
    await fs.writeFile(APP_CSS, boilerplate, 'utf8');
  }
}

async function main() {
  // 1) Collecte
  try {
    await fs.access(STYLES_DIR);
  } catch {
    console.error(`❌ Dossier introuvable: ${STYLES_DIR}`);
    process.exit(1);
  }

  const allCss = await walk(STYLES_DIR);
  // Exclusions pour éviter de s'importer soi-même
  const exclude = new Set([
    path.resolve(ASSETS_DIR, 'app.css'),
    path.resolve(STYLES_DIR, 'app.css'),
  ]);
  const files = allCss.filter(p => !exclude.has(p));

  files.sort((a,b) => a.localeCompare(b)); // ordre stable

  const importLines = files.map(buildImportLine);

  if (PRINT_IMPORTS) {
    console.log(importLines.join('\n'));
    return;
  }

  // 2) Écriture dans assets/app.css entre marqueurs
  await ensureAppCss();
  let appCss = await fs.readFile(APP_CSS, 'utf8');
  const block = `${START}\n${importLines.join('\n')}\n${END}`;

  const re = blockRegex();
  if (re.test(appCss)) {
    appCss = appCss.replace(re, block);
  } else {
    appCss = `${appCss.trimEnd()}\n\n${block}\n`;
  }

  await fs.writeFile(APP_CSS, appCss, 'utf8');

  // 3) Log sympa
  console.log(`✅ ${files.length} fichier(s) CSS importés dans ${path.relative(ROOT, APP_CSS)}`);
  for (const l of importLines) console.log(`   ${l}`);
}

main().catch(err => {
  console.error('❌ Erreur:', err);
  process.exit(1);
});
