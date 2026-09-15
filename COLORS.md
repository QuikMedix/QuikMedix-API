# QuikMedix colors

The portal follows the red accents, charcoal section headers, white cards, and
light gray canvas in the [Quickpharm CRM reference](https://www.figma.com/design/Juyrk6RlpOBEeFcUNuPAKp/Quickpharm-CRM?node-id=0-1).
The primary red, `#c90016`, comes from the existing QuikMedix branding work.

| Role | Color |
| --- | --- |
| Primary action / selected navigation | `#c90016` |
| Primary hover / pressed | `#a30012` / `#960010` |
| Soft selection | `#fff0f2` |
| Navigation band / section header | `#242424` |
| Card / page canvas | `#ffffff` / `#f5f5f5` |
| Delivered / in transit | `#198754` / `#2463a0` |
| Pending / unavailable | `#946200` / `#ad2d3a` |
| Scheduled / office | `#176b82` / `#4a4a4a` |

Sass tokens live in `resources/scss/_brand-variables.scss`. The shared color layer
in `resources/scss/brand.scss` exports CSS properties prefixed with `--qm-` and is
loaded by the application layouts after the base stylesheets.

Run `npm run build:theme` to regenerate `public/css/brand.css`. The regular Laravel
Mix build also includes this stylesheet. The light/dark Bootstrap sources import
the same brand tokens.

The checked-in `public/css/bootstrap*.css` and `public/css/app*.css` contain legacy
layout customizations beyond their Sass sources. Their primary palette and its
derived hover, focus, border, and translucent colors were updated in place. Do
not replace these screen bundles with a plain Bootstrap build. Changes to base
component colors must also be reflected in these served bundles.

Dashboard status tiles use distinct status tokens and visible labels. They do
not inherit the primary action color. The color layer works alongside the
QuikMedix logos and branding configuration described in `REBRANDING.md`.
