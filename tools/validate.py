#!/usr/bin/env python3
"""Validate the generalized public WordPress project."""
from __future__ import annotations

import json
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
THEME = ROOT / 'theme' / 'persian-barbershop'
PLUGIN = ROOT / 'plugin' / 'barbershop-core'
errors: list[str] = []

required = [
    ROOT / '.env.example', ROOT / '.gitignore', ROOT / 'compose.yaml',
    THEME / 'style.css', THEME / 'theme.json', THEME / 'functions.php', THEME / 'rtl.css', THEME / 'screenshot.png',
    THEME / 'templates' / 'front-page.html', THEME / 'parts' / 'header.html', THEME / 'parts' / 'footer.html',
    PLUGIN / 'barbershop-core.php', PLUGIN / 'assets' / 'admin.js', PLUGIN / 'assets' / 'frontend.js',
]
for path in required:
    if not path.is_file():
        errors.append(f'Missing required file: {path.relative_to(ROOT)}')

try:
    json.loads((THEME / 'theme.json').read_text(encoding='utf-8'))
except Exception as exc:
    errors.append(f'Invalid theme.json: {exc}')

all_text = '\n'.join(
    path.read_text(encoding='utf-8', errors='ignore')
    for path in ROOT.rglob('*')
    if path.is_file() and path != Path(__file__).resolve() and path.suffix.lower() in {'.php', '.html', '.css', '.json', '.md', '.txt', '.yaml', '.yml', '.py', '.pot', '.example'}
)

for forbidden in (
    'Alireza', 'علیرضا', 'Armin', 'Mirsaeid', 'Mirsaeed', 'Ebrahimi',
    'Basalam', 'basalam.com', 'Shaparak', 'Iranian postal', 'local-wordpress-password',
):
    if forbidden.lower() in all_text.lower():
        errors.append(f'Forbidden private or project-specific term found: {forbidden}')

if '127.0.0.1:${WP_PORT:-8080}:80' not in (ROOT / 'compose.yaml').read_text(encoding='utf-8'):
    errors.append('Docker port is not restricted to localhost.')
if '${DB_PASSWORD:?' not in (ROOT / 'compose.yaml').read_text(encoding='utf-8'):
    errors.append('Docker database password is not required from environment configuration.')

plugin = (PLUGIN / 'barbershop-core.php').read_text(encoding='utf-8')
for token in ('wp_verify_nonce', 'current_user_can', 'sanitize_text_field', 'esc_html', 'publication consent'):
    if token.lower() not in plugin.lower():
        errors.append(f'Plugin safeguard missing: {token}')

placeholder_count = len(re.findall(r'\[[^\]]+\]', all_text))
if placeholder_count < 10:
    errors.append('Expected generalized bracketed placeholders were not found.')

if errors:
    print('Validation failed:')
    for error in errors:
        print(f'- {error}')
    sys.exit(1)

print(f'Validation passed with {placeholder_count} placeholder references.')
