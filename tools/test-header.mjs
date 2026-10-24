#!/usr/bin/env node
/**
 * Dependency-free checks against the actual WordPress theme sources.
 * These are structural regressions, not substitutes for a rendered WP test.
 */
import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import path from 'node:path';

const root = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..');
const read = (name) => readFileSync(path.join(root, name), 'utf8');
const template = read('theme/persian-barbershop/parts/header.html');
const php = read('theme/persian-barbershop/functions.php');
const css = read('theme/persian-barbershop/style.css');
const js = read('theme/persian-barbershop/assets/js/site.js');
const matches = (text, regex) => [...text.matchAll(regex)].length;

assert.equal(matches(template, /\[bsc_cart_link\]/g), 1, 'Header must contain exactly one cart link');
assert.equal(matches(template, /\[bsc_account_link\]/g), 1, 'Header must contain exactly one account link');
assert.equal(matches(template, /\[pbs_header_product_search\]/g), 1, 'Header must contain exactly one search form');
assert.equal(matches(template, /id="pbs-header-search-panel"/g), 1, 'Search panel ID must be unique');
assert.match(template, /aria-expanded="false"/, 'Closed mobile search needs accessible state');
assert.match(template, /aria-controls="pbs-header-search-panel"/, 'Search toggle must target actual panel');

const stack = [];
for (const match of template.matchAll(/<!--\s*(\/?)wp:([\w/-]+)([\s\S]*?)-->/g)) {
  const [, closing, type, details] = match;
  if (closing) assert.equal(stack.pop(), type, 'WordPress block closing order');
  else if (!details.trimEnd().endsWith('/')) stack.push(type);
}
assert.deepEqual(stack, [], 'All WordPress block comments should be balanced');

assert.match(php, /function pbs_header_product_search\(/);
assert.match(php, /wp_unique_id\(\s*'pbs-header-search-'/, 'Search input ID must not collide with Core result forms');
assert.match(php, /name="post_type" value="product"/, 'Search must be product-only');
assert.match(php, /name="s" dir="rtl"/, 'Persian query input must be RTL');
assert.match(php, /esc_attr\( get_search_query\( false \) \)/, 'Search value must be escaped');

assert.match(css, /\.pbs-header-actions\s*\{[^}]*display:flex/, 'Top header actions must be rendered');
assert.match(css, /@media\s*\(max-width:781px\)/, 'Compact mobile breakpoint must exist');
assert.match(css, /\.pbs-js \.site-header-shell \.pbs-header-search-panel\.is-open\s*\{display:block\}/);
assert.doesNotMatch(js, /\.(?:appendChild|insertBefore|cloneNode|replaceChildren)\s*\(/, 'Do not reparent/clone header controls');
assert.match(js, /event\.key === 'Escape'/, 'Escape closes the search');
assert.match(js, /'pointerdown'/, 'Outside click closes the search');
assert.match(js, /'aria-expanded'/, 'Search state remains accessible');
assert.match(js, /querySelector\('input\[type="search"\]'\).*focus/, 'Search opening focuses the input');

console.log('PASS: one header action per type, balanced WP blocks, safe RTL product search, compact/mobile behavior hooks.');
