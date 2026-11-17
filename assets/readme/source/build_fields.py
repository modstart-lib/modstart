#!/usr/bin/env python3
# Regenerates ../fields.svg (the 58 builtin field types, grouped).
# Run from the repository root: python3 assets/readme/source/build_fields.py
# Chip widths are estimated from a monospace advance; check the render afterwards.

ROWS = [
    ("文本与内容", ["text", "textarea", "password", "richHtml", "markdown", "code", "html", "raw"], 8),
    ("数字与时间", ["number", "decimal", "currency", "percent", "rate", "date", "datetime", "time"], 10),
    ("选择与关联", ["select", "multiSelect", "radio", "checkbox", "switch", "tags", "cascader", "tree"], 15),
    ("媒体与文件", ["image", "images", "file", "files", "video", "audio", "color", "icon"], 11),
    ("结构与组合", ["json", "keyValueList", "complexFields", "dynamicFields", "custom", "display", "hidden"], 14),
]

FONT = 18
ADV = FONT * 0.6  # monospace advance


def chip_w(label):
    return round(len(label) * ADV) + 30


X0 = 200
XMAX = 1144
GAP = 8
CHIP_Y = [160, 206, 252, 298, 344]
CHIP_H = 32

out = []
out.append('''<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="420" viewBox="0 0 1200 420" role="img" aria-labelledby="fldTitle fldDesc">
  <title id="fldTitle">ModStart 内置的 58 种字段类型</title>
  <desc id="fldDesc">按文本、数字与时间、选择与关联、媒体与文件、结构与组合分组的字段类型示例，每行末尾标出该组未列出的数量。</desc>

  <rect width="1200" height="420" rx="26" fill="#F5F7FB"/>

  <g id="caption" font-family="-apple-system, BlinkMacSystemFont, 'Segoe UI', 'PingFang SC', 'Noto Sans CJK SC', 'Microsoft YaHei', sans-serif">
    <text x="56" y="92" font-size="24" font-weight="600" fill="#101828">58 种内置字段类型</text>
    <text x="56" y="124" font-size="18" fill="#667085">字段在 field() 里按名字声明，表格、表单、详情页共用同一份定义</text>
  </g>''')

out.append('  <g id="field-rows">')
for i, (label, chips, total) in enumerate(ROWS):
    y = CHIP_Y[i]
    out.append(f'    <g id="row-{i + 1}">')
    out.append(f'      <text x="56" y="{y + 22}" font-family="-apple-system, BlinkMacSystemFont, \'Segoe UI\', \'PingFang SC\', \'Noto Sans CJK SC\', sans-serif" font-size="18" fill="#475467">{label}</text>')

    x = X0
    placed = 0
    hidden = 0
    for c in chips:
        w = chip_w(c)
        # keep 72 units free so the "+N" chip also fits when something is hidden
        reserve = 72 if len(chips) > placed + 1 else 0
        if x + w > XMAX - reserve:
            hidden = len(chips) - placed
            break
        out.append(f'      <rect x="{x}" y="{y}" width="{w}" height="{CHIP_H}" rx="8" fill="#FFFFFF" stroke="#E4E7EC"/>')
        out.append(f'      <text x="{x + 14}" y="{y + 22}" font-family="ui-monospace, SFMono-Regular, Menlo, Consolas, monospace" font-size="{FONT}" fill="#344054">{c}</text>')
        x += w + GAP
        placed += 1

    remaining = total - placed
    if remaining > 0:
        label_more = f"+{remaining}"
        w = chip_w(label_more)
        out.append(f'      <rect x="{x}" y="{y}" width="{w}" height="{CHIP_H}" rx="8" fill="none" stroke="#D5DBE6" stroke-dasharray="4 4"/>')
        out.append(f'      <text x="{x + 14}" y="{y + 22}" font-family="ui-monospace, SFMono-Regular, Menlo, Consolas, monospace" font-size="{FONT}" fill="#98A2B3">{label_more}</text>')

    out.append('    </g>')
out.append('  </g>')
out.append('</svg>')

with open("assets/readme/fields.svg", "w") as f:
    f.write("\n".join(out) + "\n")
print("rows:", [(r[0], len(r[1]), r[2]) for r in ROWS], "sum:", sum(r[2] for r in ROWS))
