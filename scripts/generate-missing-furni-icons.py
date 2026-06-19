#!/usr/bin/env python3
"""Generate missing catalog furni icons (blank store images).

Catalog icons are external PNGs at hof_furni/icons/<classname>_icon.png. A few furni
(e.g. crystal_dragon and other rares) never had one generated, so they show blank in the
shop. Each furni's .nitro spritesheet contains an "icon" frame; this crops it out into the
missing PNG. Idempotent (skips ones that already exist). Needs Pillow.

Usage: python3 generate-missing-furni-icons.py
"""
import glob
import gzip
import io
import json
import os
import struct
import subprocess

from PIL import Image

FURNI = '/Users/hopstaken/AtomCMS-Sadie/public/client/assets/bundled/furniture'
ICONS = '/Users/hopstaken/AtomCMS-Sadie/public/client/assets/dcr/hof_furni/icons'
MYSQL = '/opt/homebrew/bin/mysql'


def parse_bundle(path):
    data = open(path, 'rb').read()
    pos = 0
    count = struct.unpack_from('>h', data, pos)[0]; pos += 2
    out = {}
    for _ in range(count):
        nlen = struct.unpack_from('>h', data, pos)[0]; pos += 2
        name = data[pos:pos + nlen].decode(); pos += nlen
        dlen = struct.unpack_from('>i', data, pos)[0]; pos += 4
        out[name] = data[pos:pos + dlen]; pos += dlen
    return out


def make_icon(classname):
    files = parse_bundle(os.path.join(FURNI, classname + '.nitro'))
    jraw = next(v for k, v in files.items() if k.endswith('.json'))
    meta = json.loads(gzip.decompress(jraw) if jraw[:2] == b'\x1f\x8b' else jraw)
    frames = meta['spritesheet']['frames']
    icon_key = next((k for k in frames if 'icon' in k), None)
    if not icon_key:
        return None  # no icon frame to crop
    png_raw = files[meta['spritesheet']['meta']['image']]
    if png_raw[:8] != b'\x89PNG\r\n\x1a\n':
        png_raw = gzip.decompress(png_raw)
    img = Image.open(io.BytesIO(png_raw)).convert('RGBA')
    f = frames[icon_key]['frame']
    icon = img.crop((f['x'], f['y'], f['x'] + f['w'], f['y'] + f['h']))
    out = os.path.join(ICONS, classname + '_icon.png')
    icon.save(out)
    return out


def catalog_classnames():
    sql = ("SELECT DISTINCT SUBSTRING_INDEX(fi.asset_name,'*',1) FROM catalog_items ci "
           "JOIN catalog_item_furniture_item cif ON cif.catalog_items_id=ci.id "
           "JOIN furniture_items fi ON fi.id=cif.furniture_items_id;")
    out = subprocess.run([MYSQL, '-uroot', '-h127.0.0.1', 'solanahotel', '-N', '-e', sql],
                         capture_output=True, text=True)
    return [c.strip() for c in out.stdout.splitlines() if c.strip()]


def main():
    made, skipped, failed = 0, 0, []
    for cn in catalog_classnames():
        if os.path.exists(os.path.join(ICONS, cn + '_icon.png')):
            continue
        if not os.path.exists(os.path.join(FURNI, cn + '.nitro')):
            continue
        try:
            if make_icon(cn):
                made += 1
                print('icon ->', cn + '_icon.png')
            else:
                skipped += 1
        except Exception as e:
            failed.append((cn, str(e)))
    print(f'generated {made}, no-icon-frame {skipped}, failed {len(failed)}')
    for cn, e in failed:
        print('  FAILED', cn, e)


if __name__ == '__main__':
    main()
