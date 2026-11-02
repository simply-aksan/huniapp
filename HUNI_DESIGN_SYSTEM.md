# Setup Design System HUNI

## Visual Direction

- Soft Neo-Brutalism
- Cream/off-white background
- Black/dark thick outlines
- Chunky rounded cards
- Soft offset shadows
- Playful, friendly, modern
- Clean, bukan AI-slop / generic SaaS

---

## Colors

| Role | Color |
|---|---|
| Background | `#F7F3EA` |
| Surface | `#FFFFFF` |
| Ink | `#171717` |
| Muted | `#6B6B6B` |
| Border | `#171717` |
| Lime | `#C7F36B` |
| Pink | `#FFB7C5` |
| Yellow | `#FFD966` |
| Blue | `#9ED8FF` |
| Purple | `#C9B8FF` |
| Peach | `#FFC6A5` |

---

## Typography

| Usage | Font |
|---|---|
| HUNI Logo | Comic Grizzy |
| Page Heading | Comic Grizzy |
| UI / Body | Inter |
| Button / Label / Card | Inter |
| Mono / Code / Technical Data | JetBrains Mono |

> **Comic Grizzy hanya untuk logo HUNI dan heading besar. Jangan dipakai ke seluruh UI.**

---

## Border

```css
Primary:
2px solid #171717

Heavy Illustration:
3px solid #171717
```

---

## Radius

| Type | Radius |
|---|---:|
| Small | `12px` |
| Medium | `16px` |
| Card | `20px` |
| Large | `24px` |
| Pill | `999px` |

---

## Shadow

```css
Card:
4px 4px 0 #171717

Button:
3px 3px 0 #171717

Large Card:
6px 6px 0 #171717
```

**Shadow harus hard/offset, bukan blur SaaS biasa.**

---

## Spacing

| Size | Usage |
|---:|---|
| `4px` | Micro |
| `8px` | Tight |
| `12px` | Small |
| `16px` | Default |
| `24px` | Component |
| `32px` | Section |
| `48px` | Large section |
| `64px` | Hero / major section |
| `96px` | Page section |

---

## Buttons

- Bold Inter
- Rounded/pill atau chunky rounded
- Black outline `2px`
- Hard offset shadow
- Lime sebagai primary
- Hover: sedikit `translate(2px, 2px)`, shadow berkurang
- Active: shadow semakin kecil / button terasa ditekan

### Variants

| Type | Style |
|---|---|
| Primary | Lime |
| Secondary | White |
| Accent | Pink / Yellow / Blue |
| Danger | Pink / Red tone |

---

## Cards

- White/cream surface
- `2px` black outline
- `20px` radius
- Hard offset shadow
- Jangan pakai glassmorphism
- Jangan pakai excessive gradient
- Jangan terlalu banyak dekorasi

---

## Navbar Marketing

- Selalu **boxed card**
- White/cream background
- Black outline
- Hard shadow
- Rounded
- Bukan navbar transparan/flat

---

## Illustration

- Flat illustration
- Thick dark outline
- Warna pastel HUNI
- Konsisten dengan asset yang sudah dibuat
- Transparent background jika asset memang dibuat tanpa background

---

## Core Rule

> **Jangan redesign. Reproduce the existing HUNI visual language consistently across every page.**
