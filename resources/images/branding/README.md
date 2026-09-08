# QuikMedix artwork

`quikmedix-original.png` is the original logo supplied by the app owner.

Run `php scripts/build-brand-assets.php` from the repository root to rebuild the
checked-in assets in `public/images/branding` and the legacy logo aliases. PHP GD
is required. The full logo, wordmark, and Q icon retain the original lettering
and shapes. The build removes the black matte and preserves transparency through
cropping, resizing, SVG wrappers, and ICO encoding.

The default assets use charcoal lettering and a charcoal Q for light surfaces.
The `-light` variants retain the original white lettering for dark surfaces.
Both keep the red branding and red/white capsule. The transparent full-size
masters are saved alongside the original as `quikmedix-transparent.png` and
`quikmedix-transparent-dark.png`.

The full logo is used on authentication screens. The wordmark fits navigation
headers, invoices, and delivery tickets. The Q icon is used for collapsed
navigation, browser/app icons, and web notifications. PDF tickets embed the local
PNG so their logo does not depend on a remote image server.

Set `APP_URL` to the deployment's public URL so notification icon links point to
that deployment. The `APP_NAME`, `MAIL_FROM_NAME`, and `CHATIFY_NAME` settings can
override the QuikMedix display-name defaults.

## Transparency preparation

The built-in imagegen tool was tried with this prompt:

> Remove only the solid black background, including negative spaces inside the
> Q and between letters. Output a true transparent RGBA PNG. Preserve the exact
> original logo artwork, typography, colors, proportions, positions, and framing.
> Keep “QuikMEDIX” and “YOUR HEALTH - OUR PRIORITY” unchanged. No checkerboard,
> matte fringes, drop shadows, or redesign.

That export had a baked-in checkerboard and was not used in the app. The checked-in
build script instead extracts alpha from the original pixels, removes black
edge contamination, and derives the light-surface colors without redrawing the
logo. The original upload remains intact.
