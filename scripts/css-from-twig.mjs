// scripts/css-from-twig.mjs
import fs from "node:fs";
import path from "node:path";

const TEMPLATES_DIR = process.env.TEMPLATES_DIR || "templates"; // ou "Template" si c’est ton dossier
const OUTPUT_ROOT   = process.env.CSS_OUT || "public/assets/css";
const WATCH         = process.argv.includes("--watch");

// transforme "Clients/Show" => "clients/show"
function toCssRelPath(relTwigPath) {
  // enlève l’extension .html.twig
  const noExt = relTwigPath.replace(/\.html\.twig$/i, "");
  // normalise les séparateurs
  const norm  = noExt.split(path.sep).join("/");
  // on met les dossiers en minuscule (le fichier garde son nom)
  const dir   = path.dirname(norm).toLowerCase();
  const base  = path.basename(norm); // "index" par ex.
  return path.join(dir === "." ? "" : dir, `${base}.css`);
}

function ensureDirSync(dir) {
  fs.mkdirSync(dir, { recursive: true });
}

function createCssForTwig(twigAbsPath) {
  const rel = path.relative(TEMPLATES_DIR, twigAbsPath);
  if (rel.startsWith("..")) return; // en dehors du dossier templates

  const cssRel = toCssRelPath(rel);
  const cssAbs = path.join(OUTPUT_ROOT, cssRel);

  ensureDirSync(path.dirname(cssAbs));
  if (!fs.existsSync(cssAbs)) {
    const banner = `/* Auto-created for ${path.join(TEMPLATES_DIR, rel).split(path.sep).join("/")} */
:root {}
/* Add your styles here */
`;
    fs.writeFileSync(cssAbs, banner, "utf8");
    console.log("✅ created:", cssAbs);
  } else {
    // ne pas écraser les fichiers existants
    // console.log("↩︎ exists:", cssAbs);
  }
}

function walk(dir) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const p = path.join(dir, entry.name);
    if (entry.isDirectory()) walk(p);
    else if (entry.isFile() && /\.html\.twig$/i.test(entry.name)) createCssForTwig(p);
  }
}

function runOnce() {
  if (!fs.existsSync(TEMPLATES_DIR)) {
    console.error("❌ templates dir not found:", TEMPLATES_DIR);
    process.exit(1);
  }
  walk(TEMPLATES_DIR);
}

if (WATCH) {
  // lazy import (evite d’obliger chokidar si tu fais juste un runOnce)
  const { default: chokidar } = await import("chokidar");
  runOnce();
  console.log("👀 Watching for new templates...");
  chokidar
    .watch(`${TEMPLATES_DIR}/**/*.html.twig`, { ignoreInitial: true })
    .on("add", (file) => createCssForTwig(path.resolve(file)));
} else {
  runOnce();
}
