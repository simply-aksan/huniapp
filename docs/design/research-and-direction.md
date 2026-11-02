# HUNI — Design Research & Creative Direction

> **Project:** HUNI (Property & Boarding House Management Platform)  
> **Author:** Design Lead & Senior Frontend Engineer  
> **Date:** November 2026  
> **Status:** Approved Architecture

---

## 1. Skill Stack Inspection & Operational Matrix

To ensure this redesign represents world-class design judgment rather than automated prompt output, we inspected all four installed design skills and established an operational matrix governing their roles:

| Skill | Core Philosophy & Superpower | Specific Responsibilities in HUNI | Anti-Pattern Prohibitions |
| :--- | :--- | :--- | :--- |
| **`frontend-design`** | Bespoke art direction; treating each project like an elite design studio. Grounding design in subject matter. | - Creative direction & narrative tone<br>- Bespoke typography scale<br>- Anti-slop heuristic review<br>- Indonesian boarding house domain voice | - No generic SaaS card kits<br>- No tracked-out ALL CAPS eyebrows on every header<br>- No random purple/blue gradient washes<br>- No decorative elements without purpose |
| **`emil-design-eng`** | Invisible details that compound; kinetic feel, physical responsiveness, and tactile polish. | - Custom easing curves (`cubic-bezier(0.23, 1, 0.32, 1)`)<br>- Button active states (`scale(0.98)` / `translate`)<br>- Origin-aware popovers & modals<br>- 150-220ms UI transitions | - Never animate from `scale(0)` (use `scale(0.95)` with opacity)<br>- Never use `ease-in` for entering elements<br>- No animations on high-frequency keyboard actions<br>- No slow >300ms transitions |
| **`ui-ux-pro-max`** | Systematic design intelligence, WCAG accessibility benchmarks, token architecture. | - 4.5:1 text contrast compliance<br>- >=44px touch targets on mobile<br>- Semantic status color system<br>- Clean form labels & error placement | - No placeholder-only inputs<br>- No gray-on-gray low-contrast body text<br>- No raw un-tokenized hex colors in markup<br>- No emojis used as interface icons |
| **`design-taste-frontend`** | Anti-slop frontend rules; empirical study of 247 award-winning products and 23 real work screens. | - App Screen rules (Dashboards, Tables, Analytics)<br>- 5% color budget (color on data, not chrome)<br>- Tabular figures for money (`tabular-nums font-mono`)<br>- Subtle 1px structural boundaries over clumsy shadows | - No icon-in-tinted-circle on KPI numbers<br>- No decorative sparklines with fake vanity data<br>- No zebra table striping<br>- No bloated comic-book drop shadows |

---

## 2. Research References & Cross-Product Analysis

We conducted comparative analysis across leading software interfaces with renowned design systems:

### 2.1 Linear & Raycast (Precision Utility)
- **What they do best:** Extreme density without clutter, 1px crisp dividers, keyboard-driven ergonomics, subtle active states, monochromatic structural chrome with high-intent data accents.
- **Application to HUNI:** Room listings, invoice tables, and tenant rosters use tight, structured rows with crisp status badges, zero unnecessary padding, and rapid keyboard/search navigation.

### 2.2 Stripe & Notion (Document & Financial Trust)
- **What they do best:** Uncompromising typography, clear monetary presentation, readable financial ledgers, peaceful canvas tones that reduce eye strain during extended work sessions.
- **Application to HUNI:** Rent billing, payment verification screens, and financial analytics adopt clear monetary tables, formatted invoice badges, and calm, high-contrast surfaces.

### 2.3 Arc & Framer (Warmth & Modern Craft)
- **What they do best:** Avoiding sterile cold grays; using warm off-white canvases, tactile pills, and delightful micro-interactions that feel human and friendly.
- **Application to HUNI:** The Tenant portal and AI Assistant feel approachable, supportive, and distinctly Indonesian—grounded in hospitality (*keramahan kos-kosan*) without descending into childish cartoon aesthetics.

---

## 3. Creative Direction: "Modern Precision Utility"

### Why Soft Neo-Brutalism Was Evolved:
The original Soft Neo-Brutalism utilized heavy 2px black outlines (`#171717`) and 4px-6px hard offset black drop shadows on every card, button, and input. While playful, it caused significant visual friction:
1. **Visual Fatigue:** Heavy black outlines on multi-row tables and dense forms competed directly with the actual text and numbers.
2. **Perception of Authority:** Landlords handling millions in rent transactions perceived the heavy cartoon drop shadows as novelty or prototype styling rather than an institutional-grade financial and operational tool.
3. **Screen Clamping:** Bulky card borders and massive radii ate into valuable mobile and tablet viewports.

### The New Direction:
**HUNI Modern Precision Utility** marries the warm architectural identity of Indonesian residences with modern, high-craft software engineering:
- **Canvas:** Warm Architectural Stone (`#F9F8F5`), providing a calming, tactile physical feel without muddy tones.
- **Cards & Surfaces:** Pure White (`#FFFFFF`) with razor-sharp 1px structural borders (`#E5E3DC`) and refined, subtle 1px elevation shadows (`0 1px 2px rgba(18, 20, 23, 0.05)`).
- **Ink:** Deep Rich Slate (`#121417` for primary titles, `#474D57` for secondary body, `#8A919E` for tertiary metadata).
- **Signature HUNI Accent:** A refined **Emerald Leaf & Electric Citron** system. High-visibility dark-ink citron buttons (`#C7F36B` / `#D4F852`) for key conversion CTAs, grounded by rich forest accents (`#15803D`) for financial success states.
- **Zero Emoji Chrome:** All interface icons are pure, lightweight SVG vectors from the Lucide icon set. Emojis are strictly banned from UI navigation, KPI cards, and headers.

---

## 4. Copywriting & Product Voice (Indonesian Property Domain)

All copy across Owner and Tenant workflows is refactored from generic SaaS terminology into authentic Indonesian property management language:

| Context | Before (Generic / Templated) | After (HUNI Precision Domain Language) |
| :--- | :--- | :--- |
| **Bill Status** | `Unpaid` / `Belum Dibayar` | `Menunggu Pembayaran` (Pending) / `Jatuh Tempo` (Overdue) |
| **Payment Verification** | `Check Payment` / `Verifikasi` | `Konfirmasi Kas Masuk` / `Bukti Transfer Terverifikasi` |
| **Room State** | `Occupied` / `Terisi` | `Kamar Aktif` / `Kamar Siap Huni` (Vacant) |
| **Tenant Invitation** | `Send Invitation` | `Terbitkan Undangan Sewa` |
| **Complaint SLA** | `Urgent` / `Emergency` | `Prioritas Darurat (Air / Listrik)` |
| **AI Assistant** | `AI Chatbot` | `Asisten Kos HUNI (Panduan & Tata Tertib)` |
| **Empty States** | `No data found` | Action-oriented guidance (e.g. *“Belum ada kamar terdaftar. Tambahkan kamar pertama untuk mulai mengundang penghuni.”*) |
