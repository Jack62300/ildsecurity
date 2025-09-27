// scripts/extract-styles-from-twig.mjs
import fs from "node:fs";
import path from "node:path";

/**
 * CONFIG (modifiable par variables d'env)
 *  TEMPLATES_DIR : dossier des twigs       (def: "templates")
 *  PUBLIC_DIR    : racine publique         (def: "public")
 *  CSS_ROOT      : sous-dossier pour CSS   (def: "assets/css")
 *  MODE          : "append" | "overwrite"  (def: "append")
 *  BACKUP        : "1" pour sauvegarder    (def: "1")
 *  DRY_RUN       : "1" pour simuler        (def: "0")
 */
const TEMPLATES_DIR = process.env.TEMPLATES_DIR || "templates";
const PUBLIC_DIR = process.env.PUBLIC_DIR || "public";
const CSS_ROOT = process.env.CSS_ROOT || "assets/css";
const MODE = (process.env.MODE || "append").toLowerCase(); // append | overwrite
const BACKUP = (process.env.BACKUP ?? "1") === "1";
const DRY_RUN = (process.env.DRY_RUN ?? "0") === "1";

// map "templates/Client/index.html.twig" => "assets/css/client/index.css"
function computeCssRelPath(absTwig) {
  const relTwig = path.relative(TEMPLATES_DIR, absTwig);
  const noExt = relTwig.replace(/\.html\.twig$/i, "");
  const dir = path.dirname(noExt).split(path.sep).map(s => s.toLowerCase()).join("/");
  const base = path.basename(noExt);
  const rel = dir === "." ? `${base}.css` : `${dir}/${base}.css`;
  return `${CSS_ROOT}/${rel}`; // ex: assets/css/client/index.css
}

function ensureDir(p) {
  fs.mkdirSync(p, { recursive: true });
}

function listTwigFiles(dir) {
  const out = [];
  (function walk(d) {
    for (const ent of fs.readdirSync(d, { withFileTypes: true })) {
      const p = path.join(d, ent.name);
      if (ent.isDirectory()) walk(p);
      else if (ent.isFile() && /\.html\.twig$/i.test(ent.name)) out.push(p);
    }
  })(dir);
  return out;
}

// Regex simple pour capturer <style ...>...</style> (non-gourmand).
const STYLE_RE = /<style\b[^>]*>([\s\S]*?)<\/style>/gi;

function extractStylesAndRewrite(twigPath) {
  const src = fs.readFileSync(twigPath, "utf8");

  let match;
  let cssChunks = [];
  let idx = 0;

  // Rien à faire si pas de <style>
  if (!STYLE_RE.test(src)) return { changed: false };

  // Re-scan depuis le début
  STYLE_RE.lastIndex = 0;

  const rewritten = src.replace(STYLE_RE, (_, css) => {
    cssChunks.push(css.trim());
    // 1ère balise: on la remplace par le link, les suivantes: on supprime.
    const isFirst = idx++ === 0;
    return isFirst ? "__INSERT_CSS_LINK__" : "";
  });

  const cssCombined = cssChunks.filter(Boolean).join("\n\n/* ---- */\n\n");

  const cssRelPath = computeCssRelPath(twigPath); // ex: assets/css/client/index.css
  const cssAbsPath = path.join(PUBLIC_DIR, cssRelPath);

  // Écriture CSS
  ensureDir(path.dirname(cssAbsPath));
  let finalCss = `/* Extracted from ${path.relative(process.cwd(), twigPath).split(path.sep).join("/")} on ${new Date().toISOString()} */\n\n${cssCombined}\n`;

  if (!DRY_RUN) {
    if (MODE === "overwrite" || !fs.existsSync(cssAbsPath)) {
      fs.writeFileSync(cssAbsPath, finalCss, "utf8");
    } else {
      // append avec séparation + petite dédup simple si exact même chunk à la fin
      const existing = fs.readFileSync(cssAbsPath, "utf8");
      if (!existing.includes(cssCombined)) {
        fs.appendFileSync(cssAbsPath, `\n\n/* ===== append ===== */\n\n${finalCss}`, "utf8");
      }
    }
  }

  // Remplacement du placeholder par le <link>
  const linkTag = `<link rel="stylesheet" href="{{ asset('${cssRelPath}') }}">`;
  const newTwig = rewritten.replace("__INSERT_CSS_LINK__", linkTag);

  // Backup + write
  if (!DRY_RUN) {
    if (BACKUP) {
      const bak = twigPath + ".bak";
      if (!fs.existsSync(bak)) fs.writeFileSync(bak, src, "utf8");
    }
    fs.writeFileSync(twigPath, newTwig, "utf8");
  }

  return {
    changed: true,
    twigPath,
    cssRelPath,
    cssAbsPath,
  };
}

function main() {
  if (!fs.existsSync(TEMPLATES_DIR)) {
    console.error("❌ Dossier templates introuvable:", TEMPLATES_DIR);
    process.exit(1);
  }
  const files = listTwigFiles(TEMPLATES_DIR);
  if (!files.length) {
    console.log("ℹ️  Aucun fichier .html.twig trouvé.");
    return;
  }
  let changed = 0;
  for (const f of files) {
    const res = extractStylesAndRewrite(f);
    if (res.changed) {
      changed++;
      console.log(`✅ ${DRY_RUN ? "[dry-run] " : ""}Extracted styles: ${path.relative(process.cwd(), f)} -> ${res.cssAbsPath}`);
    }
  }
  console.log(`\n🎯 Terminé. Fichiers modifiés: ${changed}/${files.length}.`);
}

main();
