#!/usr/bin/env python3
"""Conservative secret and private-data scanner for the public mirror."""
from __future__ import annotations

import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]
TEXT_SUFFIXES = {'.php', '.js', '.css', '.html', '.json', '.md', '.txt', '.yaml', '.yml', '.py', '.pot', '.example', '.sh'}
SKIP = {'.git', 'node_modules', 'vendor'}

patterns = {
    'private key': re.compile(r'-----BEGIN (?:RSA |EC |OPENSSH )?PRIVATE KEY-----'),
    'GitHub token': re.compile(r'\b(?:ghp|github_pat)_[A-Za-z0-9_]{20,}\b'),
    'AWS access key': re.compile(r'\bAKIA[0-9A-Z]{16}\b'),
    'Google API key': re.compile(r'\bAIza[0-9A-Za-z_-]{30,}\b'),
    'Slack token': re.compile(r'\bxox[baprs]-[A-Za-z0-9-]{10,}\b'),
    'generic bearer token': re.compile(r'(?i)authorization\s*:\s*bearer\s+[A-Za-z0-9._~-]{16,}'),
    'realistic email': re.compile(r'\b[A-Z0-9._%+-]+@(?!example\.(?:com|org|net|test)\b)[A-Z0-9.-]+\.[A-Z]{2,}\b', re.I),
}

allowed_password_lines = {
    'DB_PASSWORD=replace-with-a-random-local-password',
    'DB_ROOT_PASSWORD=replace-with-another-random-local-password',
}
errors: list[str] = []

for path in ROOT.rglob('*'):
    if not path.is_file() or any(part in SKIP for part in path.parts):
        continue
    if path.suffix.lower() not in TEXT_SUFFIXES and path.name not in {'.gitignore', '.gitattributes'}:
        continue
    text = path.read_text(encoding='utf-8', errors='ignore')
    for label, pattern in patterns.items():
        for match in pattern.finditer(text):
            errors.append(f'{path.relative_to(ROOT)}: possible {label}: {match.group(0)[:80]}')
    for line_no, line in enumerate(text.splitlines(), 1):
        stripped = line.strip()
        if stripped in allowed_password_lines:
            continue
        if re.search(r'(?i)(password|secret|token|api[_-]?key|merchant[_-]?id)\s*[:=]\s*["\']?[A-Za-z0-9/+_.-]{12,}', stripped):
            if '${' not in stripped and 'placeholder' not in stripped.lower() and 'replace-with' not in stripped.lower():
                errors.append(f'{path.relative_to(ROOT)}:{line_no}: suspicious assigned secret')

if errors:
    print('Secret scan failed:')
    for error in errors:
        print(f'- {error}')
    sys.exit(1)

print('Secret scan passed.')
