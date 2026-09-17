#!/usr/bin/env python3
# ============================================================
# Cuts the lighthouse out of the logo.
#
# images/logo/logo.jpg is not a mark, it is the whole lockup: light beam,
# the STURMFREI wordmark, "HAMBURGER IMPRO SHOWS" underneath it, and the
# lighthouse — 2240 x 1260 px on a flat sky-blue ground. Squeezed into a
# favicon that becomes an illegible smudge: the wordmark's letters end up
# about four pixels tall. And in the header bar the file says the name a
# second time, right next to the type that already says it.
#
# The lighthouse alone is the mark. It is the only element in the lockup
# that still reads at 32 px, and it does not repeat the wordmark.
#
#   python3 tools/make-mark.py                 # 512 px master, sky ground
#   python3 tools/make-mark.py --transparent   # keep the alpha instead
#
# What comes out is the master, and this is the only script that reads the
# logo at all: `make icons` scales the master down into the favicon, the
# home-screen icon and the mark in the header bar (tools/make-icons.sh). So
# this file needs Pillow and the icons do not — the master is committed, and
# a Mac with nothing but sips still gets the three files out of it. Run this
# again when the logo changes, not when an icon size does.
#
# Three things about this file are worth knowing before you change a flag:
#
# The tower's white bands are not white. They are the ground showing
# through — the very same value as the image's corners (147, 189, 214).
# So whatever you pass as --bg becomes the stripes as well. On a light
# ground the tower stays banded; on a dark one it closes up into a solid
# red block and stops looking like a lighthouse. Hence the default is the
# logo's own sky, and the script reports the contrast at the end.
#
# There is no hardcoded crop window. The lighthouse is the only brick-red
# thing in the file — the wordmark is navy, the beam is cream — so the
# script keys out the ground, labels what is left over and keeps only the
# pieces that contain brick. If the logo is ever redrawn a little wider or
# a little further left, this still finds the tower.
#
# The margin is 0.08 and not tighter because the mark in the header bar is
# a circle: object-fit: cover on a pill radius, see css/06-header.css. A
# circle inscribed in the square keeps the whole tower — it is tall and
# narrow, and its widest part, the base, sits where the chord is still wide
# enough for it — but at 0.04 the lantern all but touches the rim. The air
# is for that coin; in the browser tab it costs about a pixel and a half of
# tower, which is not visible at 32 px.
#
# Needs Pillow: pip install Pillow
# (sips cannot do this — it crops and scales, it does not key a colour.
# ImageMagick could, but not in one legible line, so: Pillow.)
# ============================================================

import argparse
import sys
from collections import deque

try:
    from PIL import Image
except ModuleNotFoundError:
    sys.exit("Pillow is missing — install it with:  pip install Pillow")


# --- Where the ground ends and the ink begins ----------------------------
#
# The source is a JPEG, so a flat area is never quite flat: the ground
# noises around by a value or two, and edges ring. Below FUZZ_LOW a pixel
# counts as pure ground, above FUZZ_HIGH as pure ink, and in between it is
# an antialiased edge and gets a partial alpha. Distances are plain
# Euclidean in RGB — good enough when the two colours to be told apart are
# as far apart as this sky and this brick.
FUZZ_LOW = 12
FUZZ_HIGH = 64

# Brick: red clearly ahead of the other two channels. Deliberately loose,
# it only has to hold the tower apart from navy type and cream beam.
def is_brick(r, g, b):
    return r > 70 and r - g > 25 and r - b > 25


def parse_colour(text):
    s = text.strip().lstrip("#")
    if len(s) == 3:
        s = "".join(c * 2 for c in s)
    if len(s) != 6:
        raise argparse.ArgumentTypeError(f"not a colour: {text}")
    try:
        return tuple(int(s[i:i + 2], 16) for i in (0, 2, 4))
    except ValueError:
        raise argparse.ArgumentTypeError(f"not a colour: {text}")


def relative_luminance(rgb):
    def channel(v):
        v /= 255
        return v / 12.92 if v <= 0.04045 else ((v + 0.055) / 1.055) ** 2.4
    r, g, b = (channel(v) for v in rgb)
    return 0.2126 * r + 0.7152 * g + 0.0722 * b


def contrast_ratio(a, b):
    la, lb = relative_luminance(a), relative_luminance(b)
    hi, lo = max(la, lb), min(la, lb)
    return (hi + 0.05) / (lo + 0.05)


def main():
    ap = argparse.ArgumentParser(
        description="Cut the lighthouse out of the logo, on a flat ground.")
    ap.add_argument("--source", default="public/images/logo/logo.jpg")
    ap.add_argument("--out", default="public/images/logo/lighthouse.png")
    ap.add_argument("--size", type=int, default=512,
                    help="edge length of the square output (default: 512)")
    ap.add_argument("--bg", type=parse_colour, default=None,
                    help="the flat ground, #RRGGBB (default: the logo's own)")
    ap.add_argument("--key", type=parse_colour, default=None,
                    help="the colour to key out (default: the source's corner)")
    ap.add_argument("--margin", type=float, default=0.08,
                    help="air around the tower, as a fraction of the edge "
                         "(default: 0.08, the circle in the header bar needs it)")
    ap.add_argument("--transparent", action="store_true",
                    help="write RGBA with a transparent ground instead")
    ap.add_argument("--no-flat", dest="flat", action="store_false",
                    help="keep the source's own pixels, JPEG mottling and all")
    ap.add_argument("--colours", "--colors", dest="colours", type=int, default=32,
                    help="palette size for the PNG, 0 for full colour (default: 32)")
    args = ap.parse_args()

    try:
        im = Image.open(args.source).convert("RGB")
    except FileNotFoundError:
        sys.exit(f"There is no {args.source}.")

    W, H = im.size
    data = im.tobytes()

    # The ground: sampled from the top-left corner unless told otherwise.
    # Every corner of this file carries the same value, so one is enough.
    key = args.key or tuple(data[0:3])
    bg = args.bg or key

    print(f"Source:  {args.source}  ({W} x {H})")
    print(f"Ground:  #{key[0]:02x}{key[1]:02x}{key[2]:02x} keyed out"
          f"  ->  #{bg[0]:02x}{bg[1]:02x}{bg[2]:02x}")

    # --- 1. Alpha per pixel, and where the brick is ----------------------
    kr, kg, kb = key
    span = FUZZ_HIGH - FUZZ_LOW
    alpha = bytearray(W * H)
    brick = bytearray(W * H)

    for i in range(W * H):
        j = i * 3
        r, g, b = data[j], data[j + 1], data[j + 2]
        dr, dg, db = r - kr, g - kg, b - kb
        d2 = dr * dr + dg * dg + db * db
        if d2 <= FUZZ_LOW * FUZZ_LOW:
            continue                      # ground, alpha stays 0
        d = d2 ** 0.5
        alpha[i] = 255 if d >= FUZZ_HIGH else int(255 * (d - FUZZ_LOW) / span)
        if is_brick(r, g, b):
            brick[i] = 1

    if not any(brick):
        sys.exit("Found no brick-red pixels — is this the right source image?")

    # --- 2. Keep only the pieces that contain brick ----------------------
    #
    # The tower is not one single piece: its stripes are ground, so the
    # bands are separate blots. That does not matter — every piece of the
    # tower is brick, and nothing else in the file is. Letters and beam
    # therefore fall away on their own.
    solid = 128                            # what counts as "ink" for labelling
    seen = bytearray(W * H)
    kept = bytearray(W * H)
    pieces = kept_pieces = 0

    for start in range(W * H):
        if seen[start] or alpha[start] < solid:
            continue
        pieces += 1
        blot = []
        has_brick = False
        queue = deque([start])
        seen[start] = 1
        while queue:
            i = queue.popleft()
            blot.append(i)
            if brick[i]:
                has_brick = True
            x, y = i % W, i // W
            for nx, ny in ((x - 1, y), (x + 1, y), (x, y - 1), (x, y + 1),
                           (x - 1, y - 1), (x + 1, y - 1),
                           (x - 1, y + 1), (x + 1, y + 1)):
                if 0 <= nx < W and 0 <= ny < H:
                    n = ny * W + nx
                    if not seen[n] and alpha[n] >= solid:
                        seen[n] = 1
                        queue.append(n)
        if has_brick:
            kept_pieces += 1
            for i in blot:
                kept[i] = 1

    print(f"Pieces:  {kept_pieces} of {pieces} kept (the ones with brick in them)")

    # Antialiased fringes sit below the labelling threshold, so they were
    # never part of a blot. Give the ones next to a kept pixel back — the
    # edges would be hard and jagged otherwise.
    for i in range(W * H):
        if kept[i] or not alpha[i]:
            continue
        x, y = i % W, i // W
        for nx, ny in ((x - 1, y), (x + 1, y), (x, y - 1), (x, y + 1)):
            if 0 <= nx < W and 0 <= ny < H and kept[ny * W + nx]:
                kept[i] = 2                # fringe, not core
                break
    for i in range(W * H):
        if not kept[i]:
            alpha[i] = 0

    # --- 3. The square around what is left -------------------------------
    xs0, ys0, xs1, ys1 = W, H, -1, -1
    for i in range(W * H):
        if alpha[i]:
            x, y = i % W, i // W
            if x < xs0: xs0 = x
            if x > xs1: xs1 = x
            if y < ys0: ys0 = y
            if y > ys1: ys1 = y

    tw, th = xs1 - xs0 + 1, ys1 - ys0 + 1
    print(f"Tower:   {tw} x {th} px at x {xs0}, y {ys0}")

    # The tower is tall and narrow, so the square is set by its height, and
    # the margin is air on top of that — never a crop into the tower.
    edge = int(max(tw, th) * (1 + 2 * max(args.margin, 0)))
    cx, cy = (xs0 + xs1 + 1) / 2, (ys0 + ys1 + 1) / 2

    # --- 4. The one ink colour -------------------------------------------
    #
    # The lockup was drawn as flat colour, but it is stored as a JPEG: the
    # tower is mottled by a few thousand near-identical reds, and that
    # mottling is both visible at icon sizes and murder on PNG compression.
    # The fix is to take the colour the tower actually is — the value that
    # occurs most often in its interior — and lay every fully covered pixel
    # in exactly that. Edge pixels keep their coverage as alpha, so the
    # outline stays smooth. --no-flat keeps the source pixels instead.
    ink = None
    if args.flat:
        tally = {}
        for i in range(W * H):
            if alpha[i] == 255 and brick[i]:
                j = i * 3
                c = (data[j], data[j + 1], data[j + 2])
                tally[c] = tally.get(c, 0) + 1
        ink = max(tally, key=tally.get)
        print(f"Ink:     #{ink[0]:02x}{ink[1]:02x}{ink[2]:02x}"
              f"  ({len(tally)} near-identical reds flattened into it)")

    # --- 5. Draw it ------------------------------------------------------
    #
    # Without --flat the whole keying collapses into one line: a pixel that
    # is pure ink stays as it is, pure ground becomes the new colour, and
    # everything in between moves by exactly the difference —
    #
    #     out = p + (1 - a) * (bg - key)
    #
    # which is a * ink + (1 - a) * bg written without the division. Edges
    # stay smooth and never fringe in the old sky colour.
    if args.transparent:
        out = Image.new("RGBA", (edge, edge), (0, 0, 0, 0))
    else:
        out = Image.new("RGB", (edge, edge), bg)
    put = out.load()

    ox, oy = edge / 2 - cx, edge / 2 - cy
    for i in range(W * H):
        a = alpha[i]
        if not a:
            continue
        x, y = i % W, i // W
        dx, dy = int(x + ox), int(y + oy)
        if not (0 <= dx < edge and 0 <= dy < edge):
            continue
        j = i * 3
        r, g, b = data[j], data[j + 1], data[j + 2]
        if ink:
            if args.transparent:
                put[dx, dy] = (ink[0], ink[1], ink[2], a)
            else:
                t = a / 255
                put[dx, dy] = tuple(
                    int(ink[k] * t + bg[k] * (1 - t)) for k in (0, 1, 2))
        elif args.transparent:
            # Unmix the ground back out of the edge pixels, so the mark can
            # be laid on any colour later without a blue rim.
            f = 255 / a
            put[dx, dy] = (
                min(255, max(0, int((r - kr) * f + kr))),
                min(255, max(0, int((g - kg) * f + kg))),
                min(255, max(0, int((b - kb) * f + kb))),
                a,
            )
        else:
            t = (255 - a) / 255
            put[dx, dy] = (
                min(255, max(0, int(r + t * (bg[0] - kr)))),
                min(255, max(0, int(g + t * (bg[1] - kg)))),
                min(255, max(0, int(b + t * (bg[2] - kb)))),
            )

    if args.size and args.size != edge:
        out = out.resize((args.size, args.size), Image.LANCZOS)

    # Two flat colours and their edge steps do not need 24 bits per pixel.
    # A palette of 32 puts the master at a third of the size, and the only
    # pixels that move at all are on the outline. Not vanity: this project
    # has an icon script precisely because a quarter of a megabyte was
    # being loaded to draw a 52 px circle.
    if args.colours and not args.transparent:
        out = out.quantize(colors=args.colours, method=Image.MEDIANCUT,
                           dither=Image.Dither.NONE)

    out.save(args.out, "PNG", optimize=True)

    # --- 6. Say whether the stripes still read ---------------------------
    brick_ref = ink or (107, 26, 25)
    if args.transparent:
        print("Ground:  transparent — the stripes take on whatever it is laid on")
    else:
        ratio = contrast_ratio(brick_ref, bg)
        verdict = "the stripes read clearly" if ratio >= 3 else \
                  "TOO LITTLE — the tower closes up into a solid block"
        print(f"Stripes: brick against the ground {ratio:.1f}:1 — {verdict}")

    print(f"Written: {args.out}  ({args.size or edge} px square)")


if __name__ == "__main__":
    main()
