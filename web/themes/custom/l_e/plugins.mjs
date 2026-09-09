import { copy } from 'esbuild-plugin-copy';
import { sassPlugin } from 'esbuild-sass-plugin';
import { execSync } from 'node:child_process';
import { cpSync, existsSync, mkdirSync } from 'node:fs';
import { dirname } from 'node:path';
import postcss from 'postcss';
import autoprefixer from 'autoprefixer';
import fg from 'fast-glob';

// Runs autoprefixer, which reads `.browserslistrc` to add vendor prefixes.
const processor = postcss([autoprefixer()]);

/**
 * Build a sassPlugin that post-processes compiled CSS with autoprefixer.
 *
 * @param {object} [options] Extra options forwarded to sassPlugin().
 */
export function makeSassPlugin(options = {}) {
  return sassPlugin({
    // Quiet deprecations from dependencies (Bootstrap's SCSS). `quietDeps`
    // doesn't cover everything here because Bootstrap partials are imported
    // directly, so the two deprecations Bootstrap triggers that this theme's
    // own SCSS never uses (legacy color functions and if()) are silenced
    // outright. `import` is intentionally left on so the theme's own @import
    // usage keeps warning.
    quietDeps: true,
    silenceDeprecations: ['color-functions', 'if-function'],
    async transform(css, _resolveDir, filePath) {
      const result = await processor.process(css, { from: filePath, map: false });
      return result.css;
    },
    ...options,
  });
}

/**
 * Run stylelint over the SCSS sources after each build.
 *
 */
export const stylelintPlugin = {
  name: 'stylelint',
  setup(build) {
    build.onEnd(() => {
      try {
        execSync(
          'npx stylelint "**/*.scss" --config .stylelintrc.json',
          { stdio: 'inherit' }
        );
      } catch (e) {
        console.log(e);
      }
    });
  }
};

/**
 * Copy the theme's static assets (images, icons, videos, fonts) from
 * `src/assets` into `build/assets`.
 *
 * @param {boolean} [watch] Re-copy on change during watch mode.
 */
export function copyPlugin(watch = false) {
  return copy({
    resolveFrom: 'cwd',
    assets: {
      from: ['src/assets/**/*'],
      to: ['build/assets'],
    },
    ...(watch && { watch: true }),
  });
}

/**
 * Sync the Bootstrap Icons SVGs from the npm package into the theme's Icon API
 * source directory (`assets/icons/bootstrap-icons`).
 *
 */
export const bootstrapIconsPlugin = {
  name: 'bootstrap-icons',
  setup(build) {
    let copied = false;
    build.onEnd(() => {
      // Only needs to run once per process; the source never changes at runtime.
      if (copied) {
        return;
      }
      const svgFrom = 'node_modules/bootstrap-icons/icons';
      const svgTo = 'assets/icons/bootstrap-icons';

      const fontsFrom = 'node_modules/bootstrap-icons/font/fonts';
      const fontsTo = 'build/assets/fonts/bootstrap-icons';

      // Copy SVG icons for Drupal's Icon API
      if (existsSync(svgFrom)) {
        mkdirSync(dirname(svgTo), { recursive: true });
        cpSync(svgFrom, svgTo, { recursive: true });
      }
      else {
        console.warn(
          `[bootstrap-icons] ${svgFrom} not found, run \`npm install\` so the Icon API has its SVG source.`
        );
      }

      // Copy Bootstrap Icons webfonts for CSS-based icons.
      if (existsSync(fontsFrom)) {
        mkdirSync(dirname(fontsTo), { recursive: true });
        cpSync(fontsFrom, fontsTo, { recursive: true });
      }
      else {
        console.warn(
          `[bootstrap-icons] ${fontsFrom} not found, run \`npm install\` so the webfonts has its font source.`
        );
      }

      copied = true;
    });
  }
};

/**
 * Build an esbuild `entryPoints` map for component JavaScript.
 *
 * Each `components/<name>/_<name>.js` source compiles to a sibling
 * `components/<name>/<name>.js`, the file Drupal SDC auto-attaches as the
 * component's library.
 *
 * @returns {Record<string, string>} esbuild entryPoints (outBase to source).
 */
export function componentJsEntries() {
  const entries = {};
  for (const source of fg.sync('components/**/_*.js')) {
    const outBase = source.replace(/\/_([^/]+)\.js$/, '/$1');
    entries[outBase] = source;
  }
  return entries;
}
