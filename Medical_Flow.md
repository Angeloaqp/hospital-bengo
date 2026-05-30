---
name: HGB Medical Flow
colors:
  surface: '#FFFFFF'
  surface-dim: '#d8d9e5'
  surface-bright: '#f9f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#f1f3fe'
  surface-container: '#ecedf9'
  surface-container-high: '#e6e8f3'
  surface-container-highest: '#e0e2ed'
  on-surface: '#181c23'
  on-surface-variant: '#474747'
  inverse-surface: '#2d3039'
  inverse-on-surface: '#eef0fc'
  outline: '#717786'
  outline-variant: '#c1c6d7'
  surface-tint: '#005bc1'
  primary: '#0058bc'
  on-primary: '#ffffff'
  primary-container: '#0070eb'
  on-primary-container: '#fefcff'
  inverse-primary: '#adc6ff'
  secondary: '#5e5e5e'
  on-secondary: '#ffffff'
  secondary-container: '#e2e2e2'
  on-secondary-container: '#646464'
  tertiary: '#5a5c5e'
  on-tertiary: '#ffffff'
  tertiary-container: '#737577'
  on-tertiary-container: '#fcfcfe'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#d8e2ff'
  primary-fixed-dim: '#adc6ff'
  on-primary-fixed: '#001a41'
  on-primary-fixed-variant: '#004493'
  secondary-fixed: '#e2e2e2'
  secondary-fixed-dim: '#c6c6c6'
  on-secondary-fixed: '#1b1b1b'
  on-secondary-fixed-variant: '#474747'
  tertiary-fixed: '#e1e2e4'
  tertiary-fixed-dim: '#c5c6c8'
  on-tertiary-fixed: '#191c1e'
  on-tertiary-fixed-variant: '#444749'
  background: '#f9f9ff'
  on-background: '#181c23'
  surface-variant: '#e0e2ed'
  success-blue: '#3B82F6'
  urgent-red: '#EF4444'
  warning-amber: '#F59E0B'
  priority-purple: '#8B5CF6'
  outline-subtle: rgba(0,0,0,0.05)
typography:
  display-lg:
    fontFamily: Manrope
    fontSize: 20px
    fontWeight: '800'
    lineHeight: 28px
    letterSpacing: -0.02em
  headline-md:
    fontFamily: Manrope
    fontSize: 18px
    fontWeight: '800'
    lineHeight: 24px
  title-sm:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '700'
    lineHeight: 20px
  body-md:
    fontFamily: Inter
    fontSize: 14px
    fontWeight: '500'
    lineHeight: 20px
  label-lg:
    fontFamily: Inter
    fontSize: 12px
    fontWeight: '700'
    lineHeight: 16px
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Inter
    fontSize: 10px
    fontWeight: '800'
    lineHeight: 12px
    letterSpacing: 0.1em
rounded:
  sm: 0.5rem
  DEFAULT: 1rem
  md: 1.5rem
  lg: 2rem
  xl: 3rem
  full: 9999px
spacing:
  container-padding: 2rem
  element-gap: 1.5rem
  stack-space: 1rem
  sidebar-width: 224px
  max-content-width: 1200px
---

## Brand & Style
The brand personality is high-trust, clinical, and ultra-modern. It combines the efficiency of a high-end SaaS platform with the clarity required for healthcare environments. 

The design style is **Corporate Modern with Tactile influences**. It utilizes a "Floating Card" architecture where primary containers sit on a soft neutral background, using subtle border-strokes rather than heavy shadows to define boundaries. The aesthetic is extremely clean, leaning into generous whitespace and a "High-Fidelity" finish that suggests precision and reliability.

## Colors
The palette is anchored by a vibrant **Electric Blue (#007AFF)** used for primary actions and "active" selection states. This is balanced by a strict **Pure Black (#000000)** for primary headings and icons, creating a high-contrast, authoritative feel.

Backgrounds utilize a soft **Cool Grey (#F3F4F6)** to reduce eye strain, while the interactive surfaces are kept at **Pure White (#FFFFFF)**. Status indicators (Normal, Urgent, etc.) use a semantic range of saturated tints to ensure clear communication without overwhelming the neutral base of the application.

## Typography
The system uses a dual-font approach. **Manrope** is the "Display" typeface, used for brand elements, section headers, and titles to provide a modern, friendly, yet structured look. **Inter** is the "Workhorse" typeface, used for all functional data, labels, and body text due to its exceptional legibility at small sizes.

Hierarchy is established through extreme weight contrast (Extra Bold 800 vs Medium 500) rather than large scale shifts. Micro-copy and secondary metadata utilize uppercase styling with increased letter spacing to maintain clarity.

## Layout & Spacing
The layout follows a **Fixed-Width Floating Grid** model. A persistent floating sidebar is anchored to the left, while content lives within a centered max-width container (1200px). 

Spacing is generous, using a 4px/8px base unit. Section containers use 32px (2rem) padding to create a luxurious, "breathable" feel. On mobile devices, the sidebar transitions to a bottom navigation bar or hidden drawer, and container padding reduces to 16px to maximize screen real estate.

## Elevation & Depth
Elevation is conveyed through **Tonal Layering and Soft Outlines** rather than traditional drop shadows. 

1. **Base Layer:** The light grey background (#F3F4F6).
2. **Surface Layer:** White cards with a subtle 1px border (#FFFFFF) or very low-opacity black stroke (5%).
3. **Interactive Layer:** Subtle "shimmer" or shadow-sm (diffused 4px blur, 5% opacity) applied only on hover to indicate clickability.
4. **Active State:** High-contrast color fills (Primary Blue or Black) to pull elements to the very front of the visual plane.

## Shapes
The shape language is **Hyper-Rounded (Pill-style)**. Standard containers and cards use a 32px (2rem) corner radius. Interactive elements like buttons and dropdowns use a 24px radius. Smaller internal elements (like status chips or date pickers) use a 12px radius. This consistent use of large radii softens the "medical" feel, making the software feel more accessible and human-centric.

## Components

- **Buttons:** Primary buttons are pill-shaped with high-contrast fills (Blue or Black). They should include a subtle scale-down effect (95%) on click.
- **Input Fields & Dropdowns:** These are "ghost" style—backgrounds are a light grey (surface-container-low) with no border until focused, at which point they gain a subtle black ring.
- **Cards:** Use the "Floating Card" style with 32px rounded corners and white backgrounds. Every card should feature a 1px soft border to ensure separation from the grey background.
- **Selection Controls:** Radio buttons are replaced by "Selection Cards"—large interactive tiles that flip from a grey background to the Primary Blue fill when selected.
- **Status Indicators:** Use small 6px circular "pips" in the top right of cards to indicate required actions or urgent status.
- **Navigation:** Sidebar links use a 16px radius for the hover state, with active links taking on a solid color fill and white text.