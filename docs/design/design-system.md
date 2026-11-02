# HUNI — Complete Design System Specification

> **Design System Version:** 2.0.0 (Modern Precision Utility)  
> **Framework:** Tailwind CSS v4 (`@theme` in `resources/css/app.css`) + Blade UI Components  
> **Target Runtimes:** Desktop (1440px+), Laptop (1024px-1439px), Tablet (768px-1023px), Mobile (375px-767px)

---

## 1. Foundations & Design Tokens

### 1.1 Color Architecture (The 5% Color Budget)
In accordance with `design-taste-frontend`, color is applied strictly to data and interactive states, keeping chrome and surfaces disciplined.

```css
@theme {
    /* Canvas & Surfaces */
    --color-canvas: #F9F8F5;          /* Warm architectural canvas */
    --color-surface: #FFFFFF;         /* Crisp white card background */
    --color-surface-subtle: #F3F1EC;  /* Subtle panel & secondary card */
    --color-surface-hover: #ECE9E2;   /* Hover highlight on table rows */
    
    /* Inks & Text */
    --color-ink-primary: #121417;     /* High-contrast headings and active labels */
    --color-ink-secondary: #474D57;   /* Body text, descriptions, table values */
    --color-ink-tertiary: #8A919E;    /* Captions, timestamps, disabled labels */
    --color-ink-inverted: #FFFFFF;    /* Text on dark surfaces */

    /* Structural Borders */
    --color-border-subtle: #EBE8E1;   /* Internal table rows, dividers */
    --color-border-default: #DEDAD1;  /* Card outlines, form fields */
    --color-border-strong: #121417;   /* High-contrast primary borders */

    /* HUNI Signature Brand Accents */
    --color-brand-citron: #C7F36B;    /* High-energy tactical CTA fill */
    --color-brand-emerald: #15803D;   /* Trust green, verified status */
    
    /* Semantic Status Tokens (Background, Border, Ink) */
    --color-status-success-bg: #ECFDF5;
    --color-status-success-border: #A7F3D0;
    --color-status-success-text: #065F46;

    --color-status-warning-bg: #FFFBEB;
    --color-status-warning-border: #FDE68A;
    --color-status-warning-text: #92400E;

    --color-status-danger-bg: #FEF2F2;
    --color-status-danger-border: #FECACA;
    --color-status-danger-text: #991B1B;

    --color-status-info-bg: #EFF6FF;
    --color-status-info-border: #BFDBFE;
    --color-status-info-text: #1E40AF;

    --color-status-neutral-bg: #F3F1EC;
    --color-status-neutral-border: #DEDAD1;
    --color-status-neutral-text: #474D57;
}
```

### 1.2 Typography System
- **Display & Headings:** `Inter`, weights 700 / 800, `tracking-tight` (-0.02em to -0.03em).
- **Body & UI Controls:** `Inter`, weights 400 / 500 / 600, leading 1.5.
- **Financial, Currencies, Dates, IDs:** `JetBrains Mono` / `tabular-nums font-mono`, weights 500 / 600.
  - Ensures monetary alignment in Indonesian Rupiah (`Rp 1.500.000` aligns perfectly on decimal and thousand separators across table rows).

### 1.3 Elevation & Shadows
Instead of cartoonish 4px-6px hard offset black blocks on every card, HUNI Precision uses crisp 1px structural borders backed by refined tactile depth:
- **Card Default:** `border border-[#DEDAD1] shadow-[0_1px_3px_rgba(18,20,23,0.04)]`
- **Card Elevated (Modals, Dropdowns):** `border border-[#DEDAD1] shadow-[0_10px_25px_-5px_rgba(18,20,23,0.08),0_8px_10px_-6px_rgba(18,20,23,0.04)]`
- **Tactile Key Button (Brand Primary):** `border border-[#121417] shadow-[2px_2px_0px_#121417]` with `:active` translating `translate-x-[1px] translate-y-[1px] shadow-none`.

### 1.4 Radii Hierarchy
- **Pill / Badges:** `rounded-full` (`9999px`)
- **Buttons & Form Inputs:** `rounded-[10px]` to `rounded-[12px]`
- **Cards & Data Panels:** `rounded-[16px]` to `rounded-[20px]`
- **Dialogs & Large Drawers:** `rounded-[20px]` to `rounded-[24px]`

---

## 2. Reusable Component Specifications

### 2.1 Buttons (`<x-ui.button>`)
- **`primary`:** Citron background (`#C7F36B`), black ink (`#121417`), 1.5px solid `#121417` border, `shadow-[2px_2px_0_#121417]`. Active state scales `scale-[0.98]` and collapses shadow.
- **`secondary`:** Crisp white surface (`#FFFFFF`), slate ink (`#121417`), border `#DEDAD1`, hover `#F9F8F5`.
- **`danger`:** Rose background (`#FEF2F2`), red ink (`#991B1B`), border `#FECACA`, hover `#FEE2E2`.
- **`ghost`:** Transparent background, slate ink (`#474D57`), hover bg `#F3F1EC`.

### 2.2 Status Badges (`<x-ui.badge>`)
- Compact, pill-shaped (`rounded-full py-0.5 px-2.5 text-[11px] font-semibold tracking-wide`).
- Semantic variants:
  - `aktif` / `success`: emerald fill + border.
  - `pending` / `warning`: amber fill + border.
  - `danger` / `overdue`: red fill + border.
  - `info`: blue fill + border.
  - `neutral`: subtle stone fill + border.

### 2.3 Form Inputs (`<x-ui.input>`, `<select>`, `<textarea>`)
- Height 42px (mobile touch target compliant).
- Border `#DEDAD1`, background `#FFFFFF`, text `#121417` font-medium.
- Focus state: `focus:border-[#121417] focus:ring-2 focus:ring-[#121417]/10 focus:outline-none`.
- Explicit, persistent label above input with asterisk for required fields. Clear, contextual error message directly below the input with an SVG warning icon.

### 2.4 Data Tables (`<x-ui.table>`)
- Clean, un-striped white background with 1px `#EBE8E1` row borders.
- Header row: 38px height, uppercase 11px font-bold text `#767C85` with tracking-wider.
- Body rows: 48px-56px height, subtle hover highlight `#FBFBF9`.
- Monospace tabular figures for currency and dates (`tabular-nums font-mono`).
- Numbers and amounts strictly right-aligned; text left-aligned; status badges centered or aligned.

### 2.5 Empty States (`<x-ui.empty-state>`)
- Honest framing that maintains the container's layout.
- Clean 48px SVG icon in a muted stone badge.
- Clear title + descriptive explanation of what should be here.
- Explicit primary CTA button that immediately initiates the creation workflow.

---

## 3. Motion & Micro-Interaction Guidelines (Emil Kowalski)

1. **Duration Budget:** All UI animations remain between **120ms and 220ms**.
2. **Easing:**
   - Entering elements: `cubic-bezier(0.23, 1, 0.32, 1)` (strong ease-out).
   - Moving elements: `cubic-bezier(0.77, 0, 0.175, 1)` (snappy ease-in-out).
   - Drawers: `cubic-bezier(0.32, 0.72, 0, 1)`.
3. **Scale Entrances:** Elements never scale from `0`. Entrances animate from `scale(0.96)` and `opacity: 0` to `scale(1)` and `opacity: 1`.
4. **Reduced Motion:** Every animation is wrapped in `@media (prefers-reduced-motion: no-preference)` or uses Tailwind's `motion-reduce:transition-none`.

---

## 4. Accessibility Checklist (WCAG AA Compliance)

- [x] Text contrast ratio >= 4.5:1 against respective backgrounds.
- [x] Large title contrast ratio >= 3:1.
- [x] All interactive controls have visible `:focus-visible` keyboard rings.
- [x] Icon-only buttons include `aria-label` and `title`.
- [x] Color is never used as the sole conveyor of information (text labels accompany badges).
- [x] Touch targets meet or exceed 44x44px.
