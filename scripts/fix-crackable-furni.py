#!/usr/bin/env python3
"""Stop 'furniture_crackable' furni from black-screening the Nitro client.

Crackable furni (e.g. easter13_egg_*, "Crackable Egg") ship with logicType
"furniture_crackable" in their .nitro. The Nitro client then builds crackable furni
logic that expects CrackableDataType (state/hits/target) in the room object data, but
the emulator only sends LegacyData — the logic crashes and the whole room view goes
black. The emulator has no crackable mechanic, so we downgrade the logic to
"furniture_basic" (the generic logic that renders fine with legacy data). The furni
then displays normally (just isn't crackable). Idempotent; backs up each file once.

Usage: python3 fix-crackable-furni.py [furniture_nitro_dir]
Default dir = the served client bundle.
"""
import glob
import gzip
import os
import struct
import sys

NITRO_DIR = sys.argv[1] if len(sys.argv) > 1 else \
    '/Users/hopstaken/AtomCMS-Sadie/public/client/assets/bundled/furniture'
OLD = b'"furniture_crackable"'
NEW = b'"furniture_basic"'


def read_bundle(path):
    data = open(path, 'rb').read()
    pos = 0
    count = struct.unpack_from('>h', data, pos)[0]; pos += 2
    files = []
    for _ in range(count):
        nlen = struct.unpack_from('>h', data, pos)[0]; pos += 2
        name = data[pos:pos + nlen]; pos += nlen
        dlen = struct.unpack_from('>i', data, pos)[0]; pos += 4
        files.append([name, data[pos:pos + dlen]]); pos += dlen
    return files


def write_bundle(path, files):
    out = bytearray(struct.pack('>h', len(files)))
    for name, raw in files:
        out += struct.pack('>h', len(name)) + name
        out += struct.pack('>i', len(raw)) + raw
    open(path, 'wb').write(out)


def fix(path):
    files = read_bundle(path)
    changed = False
    for entry in files:
        if not entry[0].endswith(b'.json'):
            continue
        try:
            txt = gzip.decompress(entry[1])
        except Exception:
            continue
        if OLD in txt:
            entry[1] = gzip.compress(txt.replace(OLD, NEW), mtime=0)
            changed = True
    if changed:
        bak = path + '.preCrackable.bak'
        if not os.path.exists(bak):
            import shutil
            shutil.copy2(path, bak)
        write_bundle(path, files)
    return changed


def main():
    fixed = []
    for fp in glob.glob(os.path.join(NITRO_DIR, '*.nitro')):
        try:
            if fix(fp):
                fixed.append(os.path.basename(fp)[:-6])
        except Exception as e:
            print('skip', fp, e)
    print(f'patched {len(fixed)} crackable furni in {NITRO_DIR}')
    for cn in sorted(fixed):
        print('  ', cn)


if __name__ == '__main__':
    main()
