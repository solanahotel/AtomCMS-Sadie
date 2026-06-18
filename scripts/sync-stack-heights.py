#!/usr/bin/env python3
"""Sync furniture_items.stack_height to each furni's true stacking height.

The Nitro client computes where a stacked item sits from the furni's .nitro
visualization (logic.model.dimensions.z = FURNITURE_SIZE_Z). The emulator places
the item at base.PositionZ + furniture_items.stack_height. If these disagree, items
render too high or clip inside. furnidata `height` is NOT reliable for this — the
.nitro `z` is authoritative. Run this after (re)importing furniture.

Usage: python3 sync-stack-heights.py [furniture_nitro_dir]
Defaults: dir = All-in-1-converter SWFCompiler/furniture; DB = solanahotel @127.0.0.1 root.
"""
import glob
import gzip
import json
import os
import struct
import subprocess
import sys
import zlib

NITRO_DIR = sys.argv[1] if len(sys.argv) > 1 else \
    '/Users/hopstaken/All-in-1-converter/SourceCode/SWFCompiler/furniture'
MYSQL = '/opt/homebrew/bin/mysql'


def parse_nitro(path):
    data = open(path, 'rb').read()
    pos = 0
    count = struct.unpack_from('>h', data, pos)[0]; pos += 2
    out = {}
    for _ in range(count):
        nlen = struct.unpack_from('>h', data, pos)[0]; pos += 2
        name = data[pos:pos + nlen].decode('utf8', 'replace'); pos += nlen
        dlen = struct.unpack_from('>i', data, pos)[0]; pos += 4
        out[name] = data[pos:pos + dlen]; pos += dlen
    return out


def nitro_z(path):
    files = parse_nitro(path)
    js = [n for n in files if n.endswith('.json')]
    if not js:
        return None
    raw = files[js[0]]
    txt = None
    for dec in (gzip.decompress, zlib.decompress):
        try:
            txt = dec(raw); break
        except Exception:
            pass
    if txt is None:
        txt = raw
    d = json.loads(txt)
    dim = (d.get('dimensions')
           or d.get('logic', {}).get('model', {}).get('dimensions')
           or d.get('logic', {}).get('dimensions'))
    return float(dim['z']) if dim and 'z' in dim else None


def main():
    zmap = {}
    for fp in glob.glob(os.path.join(NITRO_DIR, '*.nitro')):
        try:
            z = nitro_z(fp)
            if z is not None:
                zmap[os.path.basename(fp)[:-6]] = z
        except Exception:
            pass
    print(f'parsed {len(zmap)} furni heights from {NITRO_DIR}')

    rows = [f"('{cn.replace(chr(39), chr(39)*2)}',{z:.6f})" for cn, z in zmap.items()]
    sql = ['CREATE TEMPORARY TABLE _nz (classname VARCHAR(120) PRIMARY KEY, z DOUBLE);']
    for i in range(0, len(rows), 1000):
        sql.append('INSERT IGNORE INTO _nz (classname,z) VALUES ' + ','.join(rows[i:i + 1000]) + ';')
    sql.append("UPDATE furniture_items fi JOIN _nz nz "
               "ON SUBSTRING_INDEX(fi.asset_name,'*',1)=nz.classname "
               "SET fi.stack_height=nz.z WHERE ABS(fi.stack_height-nz.z)>0.0005;")
    sql.append('SELECT ROW_COUNT() AS rows_updated;')

    proc = subprocess.run([MYSQL, '-uroot', '-h127.0.0.1', 'solanahotel'],
                          input='\n'.join(sql), capture_output=True, text=True)
    print(proc.stdout.strip() or proc.stderr.strip())


if __name__ == '__main__':
    main()
