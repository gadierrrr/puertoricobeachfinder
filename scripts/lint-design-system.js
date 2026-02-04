#!/usr/bin/env node

const fs = require('fs');
const path = require('path');

const ROOT = path.join(__dirname, '..');
const ALLOWLIST_PATH = path.join(ROOT, 'config', 'design-lint-allowlist.json');

const EXCLUDED_DIRS = new Set([
  '.git',
  'node_modules',
  'admin',
  'api',
  'inc',
  'migrations',
  'scripts',
  'errors'
]);

const EXCLUDED_FILES = new Set([
  'offline.php'
]);

function readAllowlist() {
  if (!fs.existsSync(ALLOWLIST_PATH)) {
    return { files: {}, partials: {}, global: {} };
  }

  const data = JSON.parse(fs.readFileSync(ALLOWLIST_PATH, 'utf8'));
  return {
    files: data.files || {},
    partials: data.partials || {},
    global: data.global || {}
  };
}

function walkPhpFiles(startDir) {
  const files = [];

  function walk(dir) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const entry of entries) {
      const fullPath = path.join(dir, entry.name);
      const relPath = path.relative(ROOT, fullPath).replace(/\\/g, '/');

      if (entry.isDirectory()) {
        if (EXCLUDED_DIRS.has(entry.name)) {
          continue;
        }
        walk(fullPath);
        continue;
      }

      if (!entry.isFile() || !entry.name.endsWith('.php')) {
        continue;
      }

      if (EXCLUDED_FILES.has(entry.name)) {
        continue;
      }

      files.push(relPath);
    }
  }

  walk(startDir);
  return files.sort();
}

function lineNumberForOffset(text, offset) {
  return text.slice(0, offset).split('\n').length;
}

function matchesAnyRegex(value, regexStrings = []) {
  return regexStrings.some((regexString) => {
    const regex = new RegExp(regexString);
    return regex.test(value);
  });
}

function getFileAllow(file, allowlist) {
  return allowlist.files[file] || {};
}

function getPartialAllow(file, allowlist) {
  return allowlist.partials[file] || {};
}

function getClassTokens(content) {
  const tokens = [];
  const classRegex = /class\s*=\s*(["'])(.*?)\1/gs;
  let match;
  while ((match = classRegex.exec(content)) !== null) {
    const classBody = match[2];
    const bodyOffset = match.index + match[0].indexOf(classBody);
    const parts = classBody.split(/\s+/).map((p) => p.trim()).filter(Boolean);
    for (const token of parts) {
      const tokenOffset = bodyOffset + classBody.indexOf(token);
      tokens.push({ token, offset: tokenOffset });
    }
  }
  return tokens;
}

function lintFile(file, allowlist) {
  const fullPath = path.join(ROOT, file);
  const content = fs.readFileSync(fullPath, 'utf8');
  const issues = [];
  const fileAllow = getFileAllow(file, allowlist);

  const inlineStyleAllowed = fileAllow.allowInlineStyle === true;
  const cssLinksAllowed = fileAllow.allowCssLinks === true;
  const duplicateScaffoldAllowed = fileAllow.allowDuplicateScaffold === true;
  const collectionLightAllowed = fileAllow.allowCollectionLight === true;

  if (!inlineStyleAllowed) {
    const styleRegex = /<style\b/gi;
    let styleMatch;
    while ((styleMatch = styleRegex.exec(content)) !== null) {
      const line = lineNumberForOffset(content, styleMatch.index);
      issues.push({
        rule: 'no-inline-style-tag',
        file,
        line,
        message: 'Inline <style> tags are not allowed on public pages.'
      });
    }
  }

  if (!cssLinksAllowed && !['components/header.php', 'components/page-shell.php'].includes(file)) {
    const cssLinkRegex = /<link[^>]+href=["'][^"']*\/assets\/css\/[^"']+["'][^>]*>/gi;
    let linkMatch;
    while ((linkMatch = cssLinkRegex.exec(content)) !== null) {
      const line = lineNumberForOffset(content, linkMatch.index);
      issues.push({
        rule: 'no-direct-css-links',
        file,
        line,
        message: 'Direct /assets/css links are only allowed in shared shell/header components.'
      });
    }
  }

  if (!duplicateScaffoldAllowed) {
    const hasDoctype = /<!DOCTYPE\s+html|<!doctype\s+html/i.test(content);
    const includesShell = /components\/header\.php|components\/page-shell\.php/.test(content);
    if (hasDoctype && includesShell) {
      issues.push({
        rule: 'no-duplicated-page-scaffold',
        file,
        line: 1,
        message: 'Do not combine in-file document scaffold with shared header/page-shell include.'
      });
    }
  }

  if (!collectionLightAllowed && !['components/header.php', 'components/page-shell.php'].includes(file)) {
    const legacyVariantRegex = /collection-light/g;
    let legacyMatch;
    while ((legacyMatch = legacyVariantRegex.exec(content)) !== null) {
      const line = lineNumberForOffset(content, legacyMatch.index);
      issues.push({
        rule: 'no-collection-light-variant',
        file,
        line,
        message: 'Use homepage-first dark styling (`collection-dark` or default) for public pages.'
      });
    }
  }

  const deprecatedHeroAllowed = matchesAnyRegex(file, fileAllow.allowDeprecatedHeroGradientFiles || []);
  if (!deprecatedHeroAllowed) {
    const classTokens = getClassTokens(content);
    for (const { token, offset } of classTokens) {
      if (token === 'hero-gradient' || token === 'hero-gradient-purple') {
        const line = lineNumberForOffset(content, offset);
        issues.push({
          rule: 'no-deprecated-hero-gradient',
          file,
          line,
          message: `Deprecated class \`${token}\` detected. Use \`hero-gradient-dark\` or \`hero-gradient-guide\`.`
        });
      }
    }
  }

  const classTokens = getClassTokens(content);
  const globalAllowedColorRegex = allowlist.global.allowColorUtilityRegex || [];
  const fileAllowedColorRegex = fileAllow.allowColorUtilityRegex || [];

  for (const { token, offset } of classTokens) {
    const baseToken = token.split(':').pop() || token;
    const isDisallowedColorUtility = /^(?:bg|text|border|from|to|via|ring|fill|stroke|placeholder|accent|decoration)-(?:green|blue|teal)-\d{2,3}(?:\/\d+)?$/.test(baseToken);
    if (!isDisallowedColorUtility) {
      continue;
    }

    const isAllowed = matchesAnyRegex(baseToken, globalAllowedColorRegex) || matchesAnyRegex(baseToken, fileAllowedColorRegex);
    if (isAllowed) {
      continue;
    }

    const line = lineNumberForOffset(content, offset);
    issues.push({
      rule: 'no-disallowed-color-utilities',
      file,
      line,
      message: `Disallowed utility \`${baseToken}\` detected. Use homepage brand primitives/tokens.`
    });
  }

  return issues;
}

function lintPartials(allowlist) {
  const partialsDir = path.join(ROOT, 'assets', 'css', 'partials');
  const files = fs
    .readdirSync(partialsDir)
    .filter((name) => name.endsWith('.css'))
    .map((name) => path.join('assets', 'css', 'partials', name))
    .sort();

  const issues = [];

  const globalUtilitySelectorRegex = allowlist.global.allowUtilitySelectorRegex || [];
  const globalUnclassedAnchorSelectorRegex = allowlist.global.allowUnclassedAnchorSelectorRegex || [];

  for (const relPath of files) {
    const content = fs.readFileSync(path.join(ROOT, relPath), 'utf8');
    const partialAllow = getPartialAllow(relPath, allowlist);
    const withoutComments = content.replace(/\/\*[\s\S]*?\*\//g, '');
    const lines = withoutComments.split('\n');
    const significant = lines
      .map((line, idx) => ({ line: idx + 1, text: line.trim() }))
      .filter((entry) => entry.text.length > 0);

    if (significant.length === 0) {
      continue;
    }

    const first = significant[0];
    const last = significant[significant.length - 1];
    const propPattern = /^[a-z-]+\s*:/i;

    if (propPattern.test(first.text) || first.text === '}') {
      issues.push({
        rule: 'partials-self-contained',
        file: relPath,
        line: first.line,
        message: 'Partial starts mid-rule. Each partial must be syntactically self-contained.'
      });
    }

    if (propPattern.test(last.text) || last.text.endsWith('{')) {
      issues.push({
        rule: 'partials-self-contained',
        file: relPath,
        line: last.line,
        message: 'Partial ends mid-rule. Each partial must be syntactically self-contained.'
      });
    }

    const openCount = (withoutComments.match(/\{/g) || []).length;
    const closeCount = (withoutComments.match(/\}/g) || []).length;
    if (openCount !== closeCount) {
      issues.push({
        rule: 'partials-self-contained',
        file: relPath,
        line: 1,
        message: `Brace mismatch in partial (${openCount} \"{\" vs ${closeCount} \"}\").`
      });
    }

    const selectorRegex = /(^|})\s*([^{}@][^{}]*?)\s*\{/g;
    let selectorMatch;
    while ((selectorMatch = selectorRegex.exec(withoutComments)) !== null) {
      const selectorGroup = selectorMatch[2].trim();
      const selectorLine = lineNumberForOffset(withoutComments, selectorMatch.index);
      const selectors = selectorGroup
        .split(',')
        .map((selector) => selector.trim())
        .filter(Boolean);

      for (const selector of selectors) {
        const utilitySelectorPattern = /\.(?:bg|text|border)-(?:white|black|transparent|current|[a-z]+(?:-[a-z]+)*-\d{2,3}(?:\/\d+)?)(?=[\s.:#\[,>+~]|$)/;
        if (utilitySelectorPattern.test(selector)) {
          const allowedRegex = [
            ...globalUtilitySelectorRegex,
            ...(partialAllow.allowUtilitySelectorRegex || [])
          ];
          if (!matchesAnyRegex(selector, allowedRegex)) {
            issues.push({
              rule: 'no-global-utility-selector-overrides',
              file: relPath,
              line: selectorLine,
              message: `Utility selector override \`${selector}\` must be scoped via an approved container.`
            });
          }
        }

        if (selector.includes('a:not([class])')) {
          const allowedRegex = [
            ...globalUnclassedAnchorSelectorRegex,
            ...(partialAllow.allowUnclassedAnchorSelectorRegex || [])
          ];
          if (!matchesAnyRegex(selector, allowedRegex)) {
            issues.push({
              rule: 'no-unscoped-unclassed-anchor',
              file: relPath,
              line: selectorLine,
              message: '`a:not([class])` selectors must be scoped to approved content containers.'
            });
          }
        }
      }
    }
  }

  return issues;
}

function formatIssue(issue) {
  return `${issue.file}:${issue.line} [${issue.rule}] ${issue.message}`;
}

function lintDeadComponents(allowlist) {
  const componentsDir = path.join(ROOT, 'components');
  if (!fs.existsSync(componentsDir)) {
    return [];
  }

  const issues = [];
  const allowRegex = allowlist.global.deadComponentAllowlistRegex || [];
  const componentFiles = [];

  function walkComponents(dir) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const entry of entries) {
      const fullPath = path.join(dir, entry.name);
      if (entry.isDirectory()) {
        walkComponents(fullPath);
        continue;
      }
      if (entry.isFile() && entry.name.endsWith('.php')) {
        componentFiles.push(path.relative(ROOT, fullPath).replace(/\\/g, '/'));
      }
    }
  }

  walkComponents(componentsDir);

  const phpFiles = [];
  function walkPhp(dir) {
    const entries = fs.readdirSync(dir, { withFileTypes: true });
    for (const entry of entries) {
      if (entry.name === '.git' || entry.name === 'node_modules') {
        continue;
      }

      const fullPath = path.join(dir, entry.name);
      if (entry.isDirectory()) {
        walkPhp(fullPath);
        continue;
      }

      if (entry.isFile() && entry.name.endsWith('.php')) {
        phpFiles.push(path.relative(ROOT, fullPath).replace(/\\/g, '/'));
      }
    }
  }

  walkPhp(ROOT);

  const contents = new Map();
  for (const file of phpFiles) {
    contents.set(file, fs.readFileSync(path.join(ROOT, file), 'utf8'));
  }

  for (const componentFile of componentFiles) {
    if (matchesAnyRegex(componentFile, allowRegex)) {
      continue;
    }

    const basename = path.basename(componentFile);
    let referenced = false;
    for (const [file, content] of contents.entries()) {
      if (file === componentFile) {
        continue;
      }
      if (content.includes(basename)) {
        referenced = true;
        break;
      }
    }

    if (!referenced) {
      issues.push({
        rule: 'dead-component-file',
        file: componentFile,
        line: 1,
        message: 'Component has no include/require references. Remove it or add allowlist entry.'
      });
    }
  }

  return issues;
}

function main() {
  const allowlist = readAllowlist();
  const phpFiles = walkPhpFiles(ROOT);
  const issues = [];

  for (const file of phpFiles) {
    issues.push(...lintFile(file, allowlist));
  }

  issues.push(...lintPartials(allowlist));
  issues.push(...lintDeadComponents(allowlist));

  if (issues.length > 0) {
    console.error('✗ Design system lint failed.');
    for (const issue of issues) {
      console.error(`  - ${formatIssue(issue)}`);
    }
    process.exit(1);
  }

  console.log('✓ Design system lint passed.');
}

main();
