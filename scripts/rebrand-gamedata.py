#!/usr/bin/env python3
"""Re-apply the Solana rebrand to client gamedata (idempotent).

The gamedata under public/client/assets/gamedata is generated/converted and
gitignored, so the rebrand can't be committed as files. This script reproduces it
from freshly converted gamedata: run it after (re)generating gamedata.

Transforms (VALUES only — JSON keys and product/furni `code` identifiers are never
touched, so lookups/mappings keep working):
  - "Habbo"  -> "Solana"   (also turns "Habbo Club"->"Solana Club", "Habbo Hotel"->"Solana Hotel")
  - standalone "HC" -> "SC"
Case-sensitive, so lowercase `habbo` inside URLs/emails is left intact.

Usage: python3 rebrand-gamedata.py [gamedata_dir]
"""
import json
import re
import sys
from pathlib import Path

HC = re.compile(r'\bHC\b')


def fix(s: str) -> str:
    return HC.sub('SC', s.replace('Habbo', 'Solana'))


def walk_all_values(o):
    if isinstance(o, dict):
        return {k: walk_all_values(v) for k, v in o.items()}
    if isinstance(o, list):
        return [walk_all_values(v) for v in o]
    if isinstance(o, str):
        return fix(o)
    return o


def walk_name_desc(o):
    if isinstance(o, dict):
        return {k: (fix(v) if k in ('name', 'description') and isinstance(v, str) else walk_name_desc(v))
                for k, v in o.items()}
    if isinstance(o, list):
        return [walk_name_desc(v) for v in o]
    return o


def main():
    base = Path(sys.argv[1] if len(sys.argv) > 1
                else '/Users/hopstaken/AtomCMS-Sadie/public/client/assets/gamedata')
    jobs = [
        ('ExternalTexts.json', walk_all_values),
        ('UITexts.json', walk_all_values),
        ('FurnitureData.json', walk_name_desc),
        ('ProductData.json', walk_name_desc),
    ]
    for name, fn in jobs:
        path = base / name
        if not path.exists():
            print(f'skip (missing): {path}')
            continue
        data = json.loads(path.read_text())
        path.write_text(json.dumps(fn(data), ensure_ascii=False))
        print(f'rebranded: {name}')


if __name__ == '__main__':
    main()
