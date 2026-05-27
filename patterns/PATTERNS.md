# Skate — GreenShift Pattern Conventions

Authoritative reference for every `.php` file in `/patterns/`. All patterns must follow these rules so they stay consistent, editable in the WP block editor, and theme-aware.

---

## File header

```php
<?php
/**
 * Title: [Human-readable name]
 * Slug: skate/[kebab-case]
 * Categories: skate-heroes | skate-sections | skate-cta
 * Keywords: [comma, separated, search, terms]
 * Viewport Width: 1280
 */
?>
```

WordPress 6.0+ auto-discovers all `.php` files in `/patterns/` — no manual registration needed.

> **`inlineCssStyles` is required on every block.** This is the pre-computed CSS string that WP's block editor uses to validate GreenShift blocks. Without it, blocks fail with "Block contains unexpected or invalid content" regardless of how correct the other attributes are. **Do not hand-author this attribute** — use the repair-and-replace workflow below to let WP generate it.

> **`type: "inner"` works on any tag** — `div`, `section`, `h2`, `p`, `a`, `span`, `em`, etc. — as long as `inlineCssStyles` is present. Earlier notes restricting this to `div`/`section` only were wrong; they reflected missing `inlineCssStyles`, not a tag limitation.

> **GreenShift SVG blocks (`tag:"svg"` + `icon` attribute) work as child blocks** when `inlineCssStyles` is present. The `icon` attribute encodes the SVG as unicode-escaped JSON — WP generates this correctly during recovery. Do not hand-author it.

> **Never use raw innerHTML GreenShift blocks (no `type`, no `textContent`).** GreenShift's JS editor only recognizes `type:"inner"` (InnerBlocks) and `textContent` (RichText). A block with neither causes validation errors even though the PHP render callback handles it fine. Always use `textContent` for leaf blocks with text content.

> **No freeform HTML comments.** Any `<!-- ... -->` that does not start with `wp:` is treated as raw HTML by the block parser and wraps everything in a Classic block. Never add decorative separator comments (`<!-- ═══ -->`, `<!-- ── label ── -->`, etc.) inside pattern files.

---

## Block system convention

**GreenLight for layout, core blocks for content.**

| Use GreenLight (`greenshift-blocks/element`) for | Use core blocks for |
|---|---|
| Section wrappers, content areas | `<!-- wp:heading -->` (h1–h6) |
| Grid, flex containers | `<!-- wp:paragraph -->` |
| Cards, decorative wrappers | `<!-- wp:image -->` |
| Eyebrow labels (small styled text, non-editorial) | `<!-- wp:list -->`, `<!-- wp:quote -->` |
| Pseudo-elements (::before), hover effects | Any editorial/body content |

**Why:** Core blocks inherit all typography from `theme.json` automatically. Changing a font or size in `theme.json` propagates everywhere with no pattern edits needed. GreenLight's structural blocks don't hold editorial text, so they don't need to know about fonts.

**Corollary:** Never use a GreenLight block where a core block is sufficient. The heading and paragraph inside a card should be `wp:heading` and `wp:paragraph` — not `greenshift-blocks/element` with `tag:"h2"` and `textContent`.

### `theme.json` presets for GreenLight `styleAttributes`

**Core principle: always reuse native Gutenberg.** Font sizes and font families flow through WP presets. Use WP preset vars directly — never `--skate-size-*` aliases.

For **core blocks** (`wp:heading`, `wp:paragraph`): use the WP `fontSize` attribute (e.g., `"fontSize":"l"`) and never set `fontFamily` or `fontSize` inline — the admin Typography panel writes values directly into `theme.json` element-level typography, so all core heading and paragraph blocks update automatically.

For **GreenLight structural blocks** that genuinely need a font reference:

| Purpose | Value |
|---|---|
| Font size S | `var(--wp--preset--font-size--s)` |
| Font size M | `var(--wp--preset--font-size--m)` |
| Font size L | `var(--wp--preset--font-size--l)` |
| Font size XL | `var(--wp--preset--font-size--xl)` |
| Font size XXL | `var(--wp--preset--font-size--xxl)` |
| Heading font | `var(--wp--preset--font-family--syne)` _(hardcode the slug — GreenShift layout blocks are structural, not editorial)_ |
| Body font | `var(--wp--preset--font-family--dm-sans)` _(hardcode the slug)_ |

Colors are in the CSS token reference below.

---

## Core block stored HTML rules

When hand-authoring core blocks, the stored HTML between the block delimiters must match exactly what WP's `save()` function would output. Key rules:

| Condition | Required class on element |
|---|---|
| `style.color.text` is set (inline color) | `has-text-color` |
| `textColor` preset is set (registered color) | `has-{slug}-color has-text-color` |
| `style.backgroundColor` is set | `has-background` |
| `fontSize` preset is set (e.g. `"s"`) | `has-s-font-size` (pattern: `has-{slug}-font-size`) |
| `wp:heading` or `wp:paragraph` | `wp-block-heading` / _(no required class for paragraph)_ |

Two ways to set text color — they produce different attributes and different classes:

```html
<!-- Inline color (arbitrary value) -->
<!-- wp:paragraph {"style":{"color":{"text":"rgba(23,38,58,0.45)"}},"fontSize":"s"} -->
<p class="has-text-color has-s-font-size" style="color:rgba(23,38,58,0.45)">Text</p>
<!-- /wp:paragraph -->

<!-- Registered preset (e.g. secondary-color) -->
<!-- wp:paragraph {"textColor":"secondary-color","fontSize":"s"} -->
<p class="has-secondary-color-color has-text-color has-s-font-size">Text</p>
<!-- /wp:paragraph -->
```

Use the preset form when referencing a theme color — it stays in sync if the palette changes.

**`textColor` on `wp:heading`** works the same way — generates `has-{slug}-color has-text-color`:

```html
<!-- wp:heading {"level":3,"className":"my-title","style":{"typography":{"fontWeight":"700"}},"fontSize":"l","textColor":"main-color"} -->
<h3 class="wp-block-heading my-title has-main-color-color has-text-color has-l-font-size" style="font-weight:700">Card title</h3>
<!-- /wp:heading -->
```

⚠️ **`textColor` on headings generates `color: ... !important` in WP's stylesheet.** If you need to override it on hover (e.g. white on dark bg hover), the hover CSS must also use `!important`:
```css
.my-grid .my-card:hover .my-title { color: #fff !important; }
```

**`fontSize` preset on `wp:heading`** — removes the inline `font-size` style and adds a `has-{slug}-font-size` class:

```html
<!-- wp:heading {"level":3,"fontSize":"l"} -->
<h3 class="wp-block-heading has-l-font-size">Title</h3>
<!-- /wp:heading -->
```

Never set `fontSize` inline on a heading — use the WP preset attribute so the Typography admin panel controls it.

**Attribute order:** WP normalizes `style` before `fontSize` before `textColor` in the block comment — write `{"style":{...},"fontSize":"l","textColor":"main-color"}`.

**Section headings (`wp:heading` h2):** Write heading text as plain text — never wrap in `<strong>`:

```html
<!-- wp:heading {"style":{"spacing":{"margin":{"bottom":"clamp(2rem,4vw,3rem)"}}}} -->
<h2 class="wp-block-heading" style="margin-bottom:clamp(2rem,4vw,3rem)">Section title here</h2>
<!-- /wp:heading -->
```

If any class is missing, WP detects a mismatch between the stored HTML and `save()` output, wraps the old content in a new element, and creates nested tags (e.g. `<p><p>...</p></p>`).

---

## Repair-and-replace workflow (required finalization step)

Hand-authored pattern PHP will always be missing `inlineCssStyles`. Use this workflow after drafting any new pattern:

```
1. Insert pattern from Patterns panel in WP editor
2. Click "Attempt recovery" for any erroring blocks
3. Open Code Editor (⋮ → Code editor, or Ctrl+Shift+Alt+M)
4. Select + copy the entire block markup for the section
5. Paste it here — strip to just the block markup, re-add PHP header, write to the pattern file
6. Delete the test page (don't save)
7. Run `npm run check:ids` — must exit with no duplicates before committing
8. Re-insert the pattern → should show 0 errors
```

After substitution, verify these attributes survived WP's normalization:
- Section: `"isVariation":"contentwrapper"` and `"dynamicAttributes":[{"name":"data-type","value":"section-component"}]`
- Title: `"isVariation":"contentarea"`, `"metadata":{"name":"Title"}`, `"dynamicAttributes":[{"name":"data-type","value":"content-area-component"}]`, `"animation":{"duration":800,"easing":"ease","type":"fade-up"}`
- Content Area: `"isVariation":"contentarea"`, `"metadata":{"name":"Content Area"}`, `"dynamicAttributes":[{"name":"data-type","value":"content-area-component"}]`, `"animation":{"duration":800,"easing":"ease","type":"fade-up"}`

If any are missing, add them back manually.

The **first pattern section** also needs a `metadata` attribute on the section wrapper so WP associates it with the pattern:

```json
"metadata": {
  "categories": ["skate-sections"],
  "patternName": "skate/my-pattern",
  "name": "My Pattern"
}
```

---

## Section structure (standard)

Every pattern must follow this exact 4-block hierarchy:

```
Section element  (tag:"section", align:"full", bg color if any)
  wp:spacer      (top breathing room — clamp(4rem,8vw,7rem))
  Title          (GreenShift div, max-width 1200px, fade-up animation)
                 Contains: eyebrow paragraph + section heading + optional controls/links
  Content Area   (GreenShift div, max-width 1200px, fade-up animation)
                 Contains: all remaining content (cards, accordions, slider, etc.)
  wp:spacer      (bottom breathing room — clamp(4rem,8vw,7rem))
```

**Why the Title / Content Area split:** they animate separately — heading slides in first, content follows. It also makes spacing between the two independently adjustable without touching the section wrapper.

**Spacing between Title and Content Area:** add `"marginBottom":["60px"]` to the Title block's `styleAttributes` and `.CLASSID{margin-bottom:60px;}` to its `inlineCssStyles`. No responsive override — `nocolumncontent` patterns use `row-gap:60px` with no breakpoint changes, and this value must stay in sync. Do not rely on `row-gap` on the section wrapper — that would also affect the spacers above and below.

**Why spacers instead of section padding:** spacer blocks are discrete WP editor blocks. Editors can shrink or remove vertical breathing room per-section without touching section-level attributes.

### GreenLight block attributes

| Block | `isVariation` | `dynamicAttributes` value | `metadata.name` |
|---|---|---|---|
| Section | `"contentwrapper"` | `section-component` | set via full `metadata` object |
| Title | `"contentarea"` | `content-area-component` | `"Title"` |
| Content Area | `"contentarea"` | `content-area-component` | `"Content Area"` |

### Fade-up animation

Both Title and Content Area get the GreenShift scroll animation. Add to the block JSON:

```json
"animation": {"duration": 800, "easing": "ease", "type": "fade-up"}
```

Append to `inlineCssStyles` (after the layout CSS, replace `CLASSID` with the block's actual ID):

```css
.CLASSID{transition-duration:0.8s;}
.CLASSID{transition-timing-function:var(--gs-root-animation-easing, cubic-bezier(0.42, 0, 0.58, 1));}
.CLASSID{opacity: var(--gs-root-animation-opacity, 0);transition-property: opacity, transform, filter;}
.CLASSID.aos-animate,.CLASSID[data-gs-aos]{opacity: 1;transform: translateZ(0);}
.CLASSID{transform: var(--gs-root-animation-transform, translate3d(0, calc(max(50px, 15%)), 0));}
```

Add to the rendered HTML opening tag:

```html
<div data-aos="fade-up" data-aos-easing="ease" data-aos-duration="800" class="CLASSID" data-type="content-area-component">
```

### Spacer block

Use the native Gutenberg spacer with a `clamp()` height so it stays fluid:

```html
<!-- wp:spacer {"height":"clamp(4rem,8vw,7rem)"} -->
<div style="height:clamp(4rem,8vw,7rem)" aria-hidden="true" class="wp-block-spacer"></div>
<!-- /wp:spacer -->
```

### Horizontal padding

Applied on the **Section wrapper** (Level 1) via `paddingLeft/Right`:

```json
"paddingLeft":  ["clamp(1.5rem,5vw,4rem)"],
"paddingRight": ["clamp(1.5rem,5vw,4rem)"]
```

---

## Accessibility checklist

Apply these rules to every new pattern before committing.

### Decorative icons and SVGs

Icons and SVGs that communicate nothing to screen readers must be hidden from the accessibility tree. For GreenShift `type:"inner"` icon containers (e.g. a colored bubble wrapping an SVG), use `dynamicAttributes`:

```json
"dynamicAttributes": [{"name": "aria-hidden", "value": "true"}]
```

And on the rendered HTML element:

```html
<div aria-hidden="true" class="skate-svc-icon gsbp-XXXXXXX">
```

For GreenShift SVG blocks (`tag:"svg"`), add `aria-hidden="true"` to the rendered `<svg>` tag directly. Note: this may need re-adding after the repair-and-replace workflow, since GreenShift's SVG `save()` may not persist `dynamicAttributes`.

### Decorative text (watermarks, separators)

Large ghost-text watermarks and purely decorative spans must also be hidden:

```json
"dynamicAttributes": [{"name": "aria-hidden", "value": "true"}]
```

```html
<span aria-hidden="true" class="gsbp-XXXXXXX">Berlin</span>
```

### Slider / carousel navigation controls

Div-based nav controls (`.skate-prev`, `.skate-next`) need a label so screen readers can announce them:

```json
"dynamicAttributes": [{"name": "aria-label", "value": "Previous project"}]
```

```html
<div class="skate-prev gsbp-XXXXXXX" aria-label="Previous project">
```

### Heading hierarchy

- h2 = section title (one per pattern section)
- h3 = card/item title within the section
- Stat numbers, counter headings, and sub-item headings must be h3 or lower — never h2

### Contact and address information

Wrap contact blocks (address + email/phone) in a GreenShift block with `"tag":"address"`:

```json
{"tag": "address", "type": "inner", ...}
```

```html
<address class="gsbp-XXXXXXX">...</address>
```

### `prefers-reduced-motion`

Handled globally in `assets/scss/main.scss` — no per-pattern action needed. The `hero-fx.js` and `parallax.js` JS files also check `window.matchMedia('(prefers-reduced-motion: reduce)')` and exit early.

---

## Block anatomy

| Attribute | Rule |
|---|---|
| `id` | `gsbp-` + exactly **7** alphanumeric chars. Must equal `localId`. Unique per file. |
| `localId` | Same value as `id`. |
| `type: "inner"` | Container — has child blocks. |
| `type: "no"` | Decorative / structural empty element (no children, no text). |
| _(no type)_ + `textContent` | Text leaf — no child blocks. |
| `tag` | HTML element. Defaults to `div` if omitted. |
| `align: "full"` | Full-width breakout. Only on Level 1 section wrappers. |
| `className` | Extra CSS classes added alongside the block ID class. |

### `styleAttributes` format

All values are arrays. A single-item array applies to all breakpoints:

```json
"styleAttributes": {
  "fontSize": ["16px"],
  "fontWeight": ["700"]
}
```

---

## Responsive arrays

Four-slot arrays map to `[desktop, tablet, mobile_landscape, mobile_portrait]`:

```json
"gridTemplateColumns": ["1fr 1fr", "1fr", "1fr", "1fr"]
```

Provide only as many slots as needed — trailing slots inherit the last value.

---

## CSS token reference

### Colors

| Purpose | Value to use |
|---|---|
| Dark navy bg / text | `var(--wp--preset--color--main-color)` |
| Brand accent (orange) | `var(--wp--preset--color--secondary-color)` — also `"textColor":"secondary-color"` in core blocks, or `"fill":["var(--wp--preset--color--secondary-color, #ff5500)"]` in GreenLight |
| Light warm bg | `var(--wp--preset--color--light-gray)` |
| White bg | `#fff` |
| Accent teal | `#4db8a4` _(hardcoded — client brand, not in WP palette)_ |
| Muted text on **dark** bg | `rgba(255,255,255,.45)` |
| Muted text on **light** bg | `rgba(23,38,58,.45)` |
| Subtle rule on dark | `rgba(255,255,255,.08)` |
| Subtle rule on light | `rgba(23,38,58,.08)` |

### Typography

| Purpose | Value to use |
|---|---|
| Heading font | `var(--wp--preset--font-family--syne)` |
| Body font | `var(--wp--preset--font-family--dm-sans)` |

### Theme utility classes

Always use these for cards instead of hardcoded values:

| Class | What it applies |
|---|---|
| `skate-radius` | `border-radius: var(--skate-radius); overflow: hidden` |
| `skate-shadow` | `box-shadow: var(--skate-shadow)` |

**Rule:** every card element must have `"className":"skate-radius skate-shadow"` in the block JSON and those classes in the rendered HTML. Never hardcode `borderRadius` or `overflow: hidden` on a card.

---

## Common recipes

### Grid vs flex for card/item containers

**Rule:** multi-column card and item layouts (feature grids, team grids, service grids, logo rows, etc.) must use `display:grid` on the container, not `display:flex; flex-wrap:wrap` with `nth-child` width calculations.

**Why:** flex + nth-child width rules require fragile `calc(N% - gap * (N-1) / N)` math that produces rounding errors and breaks when content height varies across columns. CSS grid's `repeat(N, minmax(0, 1fr))` handles all of that natively and is simpler to read and override at breakpoints.

```json
// Container styleAttributes
"display": ["grid"],
"gridTemplateColumns": ["repeat(3,minmax(0,1fr))", "repeat(2,minmax(0,1fr))", "repeat(1,minmax(0,1fr))"],
"columnGap": ["40px"],
"rowGap": ["40px"]
```

```css
/* inlineCssStyles */
.gsbp-XXXXXXX{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:40px;}
@media (max-width: 991.98px){.gsbp-XXXXXXX{grid-template-columns:repeat(2,minmax(0,1fr));}}
@media (max-width: 767.98px){.gsbp-XXXXXXX{grid-template-columns:repeat(1,minmax(0,1fr));}}
```

Flex is still appropriate for single-axis layouts where items don't need to align to a grid (e.g. a row of tags, a button group, an eyebrow + icon inline combination).

---

### Equal-width flex columns (robust alternative to `calc()`)

When all flex children should share available space equally and their count is fixed, use `width:auto; flex-grow:1` on each child instead of `calc(N% - gap)`. This is more robust when content length varies and avoids the rounding errors that cause one column to wrap to the next row.

```json
// On each child element's styleAttributes:
"width": ["auto"],
"flexGrow": ["1"]
```

```css
/* Equivalent inlineCssStyles on the child: */
.gsbp-CHILD { width: auto; flex-grow: 1; }
```

**Remove** the `nth-child` width rules from the container's `inlineCssStyles` entirely — do not rely on the child's `width:auto` to override them. The `nth-child` selector (`.parent > :nth-child(...)`) has higher specificity than the child's own class selector (`.child`), so it wins even when `width:auto` is set on the child.

---

### Card block

```html
<!-- wp:greenshift-blocks/element {"id":"gsbp-XXXXXXX","type":"inner","localId":"gsbp-XXXXXXX","className":"skate-radius skate-shadow","styleAttributes":{"backgroundColor":["#fff"],"padding":["2rem"],"position":["relative"]}} -->
<div class="gsbp-XXXXXXX skate-radius skate-shadow">
  ...
</div>
<!-- /wp:greenshift-blocks/element -->
```

### Card hover transitions

Every property that changes on `:hover` must have a matching `transition` on the **base state** — never on the hover rule itself. CSS transitions are bidirectional: the same declaration controls both enter and exit.

| Element | Where to set | `transition` value |
|---|---|---|
| Card wrapper | block `styleAttributes` + `inlineCssStyles` | `background-color .3s` |
| Icon bubble | block `styleAttributes` + `inlineCssStyles` | `background-color .3s, color .3s` |
| Card link | block `styleAttributes` + `inlineCssStyles` | `color .3s, border-color .3s` |
| Title, body text | `dynamicGClasses` selector on the grid block | `color .3s` |

Add the `dynamicGClasses` selectors on the **grid** block (not individual cards) for descendant text elements:

```json
{"value":" .skate-svc-title", "css": ".skate-svc-grid .skate-svc-title{transition:color .3s;}"},
{"value":" .skate-svc-body",  "css": ".skate-svc-grid .skate-svc-body{color:rgba(23,38,58,.50);transition:color .3s;}"},
{"value":" .skate-svc-icon svg", "css": ".skate-svc-grid .skate-svc-icon svg{fill:currentColor;transition:fill .3s;}"}
```

**Rule:** never define a `:hover` color or background change without a matching base-state `transition` on that element. This applies to all card-style patterns.

---

### `dynamicGClasses` — correct format

GreenShift scoped CSS. **Do not hand-author** — use the repair-and-replace workflow. For reference, the real format WP generates:

```json
"dynamicGClasses": [{
  "value": "my-grid",
  "type": "local",
  "label": "my-grid",
  "localed": false,
  "css": "",
  "attributes": { "styleAttributes": [] },
  "originalBlock": "greenshift-blocks/element",
  "selectors": [
    {
      "value": " .my-card:hover",
      "attributes": { "styleAttributes": { "backgroundColor": ["var(--wp--preset--color--main-color)"] } },
      "css": ".my-grid .my-card:hover{background-color:var(--wp--preset--color--main-color);}"
    },
    {
      "value": " .my-card:hover .my-title",
      "attributes": { "styleAttributes": { "color": ["#fff"] } },
      "css": ".my-grid .my-card:hover .my-title{color:#fff !important;}"
    }
  ]
}]
```

Key fields: `"value"` = the class name (not `"class"`); `"selectors"` = the rules array (not `"styles"`). Each selector has `"value"` (CSS selector suffix), `"attributes.styleAttributes"` (for the editor UI), and `"css"` (the actual CSS string injected). The `"css"` in each selector is also concatenated into `inlineCssStyles` on the block.

The `"value"` selector is appended to `.my-grid` (the base class), so `" .my-card:hover"` (leading space = descendant) produces `.my-grid .my-card:hover`.

### `::before` pseudo-element (e.g. teal top bar)

After repair, WP generates a selector entry like:

```json
{
  "value": "::before",
  "attributes": { "styleAttributes": { "position": ["absolute"], "top": ["0"], "left": ["0"], "right": ["0"], "height": ["2px"], "backgroundColor": ["#4db8a4"] } },
  "css": ".my-card::before{content:'';position:absolute;top:0;left:0;right:0;height:2px;background-color:#4db8a4;}"
}
```

The parent must have `"position":["relative"]`.

### SVG icon

GreenShift SVG blocks (`tag:"svg"` + `icon` attribute) work as child blocks when `inlineCssStyles` is present. **Do not hand-author them** — WP generates the unicode-escaped `icon` attribute correctly during the repair-and-replace workflow.

After repair, WP produces one of two icon types depending on which picker was used:

| `icon.type` | How it renders | Rendered HTML |
|---|---|---|
| `"svg"` | Inline SVG from `icon.icon.svg` field | Simple line-art `<svg>` paths (24×24 viewBox) |
| `"font"` | Rhicon icon font class in `icon.icon.font` (e.g. `rhicon rhi-map-marker-alt`) | Font's path data SVG (different viewBox, e.g. 768×1024) |

Both are valid. The rendered SVG paths in the HTML differ visually — type:font uses rhicon's stroke-style glyphs, which are bolder and pixel-perfect at small sizes.

**Do not put raw SVG inside a `type:"inner"` container.** WP treats `type:"inner"` blocks as InnerBlocks containers and discards any raw HTML that isn't a valid child block comment — the SVG will survive the PHP render but will be stripped on recovery, leaving an empty div.

Two valid approaches:

**A — GreenShift SVG child block (preferred, survives recovery):**

Use this when the icon container is `type:"inner"`. Hand-author the `svgRaw` field; WP generates the rest during repair.

```html
<!-- wp:greenshift-blocks/element {"id":"gsbp-XXXXXXX","type":"inner",...} -->
<div class="gsbp-XXXXXXX" aria-hidden="true"><!-- wp:greenshift-blocks/element {"id":"gsbp-YYYYYYY","tag":"svg","icon":{"icon":{"font":"","svg":"","image":"","svgRaw":"<svg viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"white\" stroke-width=\"2\"><polyline points=\"22 12 18 12 15 21 9 3 6 12 2 12\"></polyline></svg>"},"type":"svg"},"localId":"gsbp-YYYYYYY","inlineCssStyles":".gsbp-YYYYYYY{width:22px;height:22px;}","styleAttributes":{"width":["22px"],"height":["22px"]}} -->
<svg class="gsbp-YYYYYYY" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline></svg>
<!-- /wp:greenshift-blocks/element --></div>
<!-- /wp:greenshift-blocks/element -->
```

**B — Raw innerHTML (draft only, no `type:"inner"`):**

Remove `type` from the container. The SVG is raw HTML stored between the delimiters. WP converts it to approach A during repair-and-replace.

```html
<!-- wp:greenshift-blocks/element {"id":"gsbp-XXXXXXX","localId":"gsbp-XXXXXXX","styleAttributes":{"display":["flex"],"alignItems":["flex-start"],"gap":["8px"],...}} -->
<div class="gsbp-XXXXXXX"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:14px;height:14px;color:#4db8a4;flex-shrink:0;margin-top:3px">YOUR_SVG_PATHS</svg><span>Text content</span></div>
<!-- /wp:greenshift-blocks/element -->
```

### Eyebrow text

Use a core `wp:paragraph` — native Gutenberg, no GreenShift block needed:

```html
<!-- wp:paragraph {"style":{"typography":{"letterSpacing":"0.2em","textTransform":"uppercase","fontWeight":"700"},"spacing":{"margin":{"bottom":"0.9rem"}}},"textColor":"secondary-color","fontSize":"s"} -->
<p class="has-secondary-color-color has-text-color has-s-font-size" style="margin-bottom:0.9rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase">What we do</p>
<!-- /wp:paragraph -->
```

For a muted eyebrow (not accent color), use inline color instead of `textColor`:

```html
<!-- wp:paragraph {"style":{"color":{"text":"rgba(23,38,58,.45)"},"typography":{"letterSpacing":"0.2em","textTransform":"uppercase","fontWeight":"700"},"spacing":{"margin":{"bottom":"0.9rem"}}},"fontSize":"s"} -->
<p class="has-text-color has-s-font-size" style="color:rgba(23,38,58,.45);margin-bottom:0.9rem;font-weight:700;letter-spacing:0.2em;text-transform:uppercase">Label</p>
<!-- /wp:paragraph -->
```

Use `rgba(23,38,58,.45)` on light bg, `rgba(255,255,255,.45)` on dark bg.

### Heading with italic em

Do **not** use `type:"inner"` on heading elements (`h1`–`h6`) — it causes "Block contains unexpected or invalid content" errors in the editor. Instead, store the full heading HTML as raw innerHTML and style the `<em>` via a `dynamicGClasses` descendant selector:

```html
<!-- wp:greenshift-blocks/element {"id":"gsbp-XXXXXXX","tag":"h2","localId":"gsbp-XXXXXXX","className":"my-heading","styleAttributes":{"fontFamily":["var(--wp--preset--font-family--syne)"],"fontSize":["clamp(26px,3vw,38px)"],"fontWeight":["800"],"letterSpacing":["-.03em"]},"dynamicGClasses":[{"class":"my-heading","styles":[{"selector":" em","styleAttributes":{"fontStyle":["italic"],"fontWeight":["300"],"fontFamily":["var(--wp--preset--font-family--dm-sans)"],"display":["block"]}}]}]} -->
<h2 class="gsbp-XXXXXXX my-heading">Normal part <em>Italic part</em></h2>
<!-- /wp:greenshift-blocks/element -->
```

The `" em"` selector (leading space = descendant) targets the raw `<em>` inside the heading. No child GreenShift blocks are needed — the `<em>` is raw HTML stored between the block delimiters.

### Ghost watermark text

A large, near-invisible word placed decoratively inside a card or section. Absolute-positioned so it bleeds out of the card bottom. Use after the repair workflow — WP generates the correct `inlineCssStyles`.

Structure: outer container (overflow:hidden) → inner span (the text).

```json
// Outer container
"styleAttributes": {
  "position": ["absolute"],
  "bottom": ["-10px"],
  "right": ["10px"],
  "pointerEvents": ["none"],
  "lineHeight": ["1"],
  "overflow": ["hidden"]
}

// Inner span (textContent = city/word)
"tag": "span",
"textContent": "Berlin",
"styleAttributes": {
  "fontFamily": ["var(--wp--preset--font-family--syne)"],
  "fontSize": ["clamp(48px,7vw,80px)"],
  "fontWeight": ["800"],
  "letterSpacing": ["-.05em"],
  "color": ["rgba(23,38,58,.04)"],
  "whiteSpace": ["nowrap"],
  "display": ["block"]
}
```

The parent card must have `"position":["relative"]`. The `overflow:hidden` on the outer container clips the ghost text so it doesn't bleed into adjacent cards.
