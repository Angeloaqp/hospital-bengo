---
name: Tactile Editorial Dark
colors:
  surface: '#131313'
  surface-dim: '#131313'
  surface-bright: '#393939'
  surface-container-lowest: '#0e0e0e'
  surface-container-low: '#1c1b1b'
  surface-container: '#201f1f'
  surface-container-high: '#2a2a2a'
  surface-container-highest: '#353534'
  on-surface: '#e5e2e1'
  on-surface-variant: '#c4c7c8'
  inverse-surface: '#e5e2e1'
  inverse-on-surface: '#313030'
  outline: '#8e9192'
  outline-variant: '#444748'
  surface-tint: '#c6c6c7'
  primary: '#ffffff'
  on-primary: '#2f3131'
  primary-container: '#e2e2e2'
  on-primary-container: '#636565'
  inverse-primary: '#5d5f5f'
  secondary: '#c7c6c6'
  on-secondary: '#2f3131'
  secondary-container: '#484949'
  on-secondary-container: '#b8b8b8'
  tertiary: '#ffffff'
  on-tertiary: '#303030'
  tertiary-container: '#e4e2e1'
  on-tertiary-container: '#656464'
  error: '#ffb4ab'
  on-error: '#690005'
  error-container: '#93000a'
  on-error-container: '#ffdad6'
  primary-fixed: '#e2e2e2'
  primary-fixed-dim: '#c6c6c7'
  on-primary-fixed: '#1a1c1c'
  on-primary-fixed-variant: '#454747'
  secondary-fixed: '#e3e2e2'
  secondary-fixed-dim: '#c7c6c6'
  on-secondary-fixed: '#1a1c1c'
  on-secondary-fixed-variant: '#464747'
  tertiary-fixed: '#e4e2e1'
  tertiary-fixed-dim: '#c8c6c5'
  on-tertiary-fixed: '#1b1c1c'
  on-tertiary-fixed-variant: '#474746'
  background: '#131313'
  on-background: '#e5e2e1'
  surface-variant: '#353534'
typography:
  display-lg:
    fontFamily: Newsreader
    fontSize: 64px
    fontWeight: '600'
    lineHeight: 72px
    letterSpacing: -0.02em
  headline-lg:
    fontFamily: Newsreader
    fontSize: 40px
    fontWeight: '500'
    lineHeight: 48px
  headline-lg-mobile:
    fontFamily: Newsreader
    fontSize: 32px
    fontWeight: '500'
    lineHeight: 40px
  headline-md:
    fontFamily: Newsreader
    fontSize: 28px
    fontWeight: '500'
    lineHeight: 36px
  body-lg:
    fontFamily: Be Vietnam Pro
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 28px
  body-md:
    fontFamily: Be Vietnam Pro
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 24px
  label-md:
    fontFamily: Be Vietnam Pro
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
    letterSpacing: 0.05em
  label-sm:
    fontFamily: Be Vietnam Pro
    fontSize: 12px
    fontWeight: '500'
    lineHeight: 16px
rounded:
  sm: 0.25rem
  DEFAULT: 0.5rem
  md: 0.75rem
  lg: 1rem
  xl: 1.5rem
  full: 9999px
spacing:
  base: 8px
  container-padding-desktop: 40px
  container-padding-mobile: 20px
  gutter: 24px
  stack-sm: 12px
  stack-md: 24px
  stack-lg: 48px
---

## Brand & Style
This design system bridges the gap between high-end print editorial and modern digital tactility. It is designed for sophisticated content consumption, targeting a discerning audience that values focus, legibility, and premium aesthetics. 

The visual style is a blend of **Minimalism** and **Tactile** design. It rejects the flat trend in favor of subtle physical cues—depth, soft shadows, and layered surfaces—while strictly adhering to a "No-Line" philosophy. Instead of borders to define boundaries, the system relies on tonal shifts between deep charcoal values and soft ambient shadows. The result is a UI that feels carved and intentional rather than outlined, evoking the emotional response of a luxury dark-mode reading experience.

## Colors
The palette is rooted in a "Deep Onyx" spectrum to minimize eye strain while maintaining a sense of luxury. 

- **Base Surface:** #121212 serves as the canvas for all layouts.
- **Elevated Surface:** #181818 is the primary container color, creating a soft "step up" from the background without the need for strokes.
- **Accents:** Pure White (#FFFFFF) is used sparingly for primary actions and critical text to provide maximum contrast against the dark surfaces. 
- **Muted Tones:** Secondary text and decorative elements use #A0A0A0 to create a clear hierarchy that doesn't compete with the primary content.
- **Interaction States:** Subtle shifts to #242424 are used for hover states on containers.

## Typography
Typography is the cornerstone of the editorial aesthetic. We pair the authoritative, literary feel of **Newsreader** for headlines with the contemporary, warm clarity of **Be Vietnam Pro** for functional UI and body copy.

Headlines should utilize generous leading (line height) to maintain an airy, sophisticated feel. Large display type should use a slight negative letter-spacing to appear tighter and more professional. Labels are set in Be Vietnam Pro with increased letter-spacing and uppercase styling to provide a clear functional distinction from editorial content.

## Layout & Spacing
The layout follows a **Fixed Grid** philosophy to mirror the structured nature of a printed magazine. 

- **Desktop:** 12-column grid with a 1200px max-width, 24px gutters, and 40px side margins.
- **Tablet:** 8-column grid with 24px gutters and 32px margins.
- **Mobile:** 4-column fluid grid with 16px gutters and 20px margins.

Spacing is governed by an 8px scale. High-level sections should utilize "Stack-LG" (48px) to allow content to breathe, emphasizing the minimal aesthetic. Content within cards or modules should use "Stack-SM" (12px) to maintain a tight, tactile relationship between related elements.

## Elevation & Depth
Depth is achieved through **Tonal Layering** and **Ambient Shadows**. This design system avoids borders entirely.

1.  **Level 0 (Base):** #121212. The ground floor.
2.  **Level 1 (Cards/Surface):** #181818. These elements feature a very subtle, diffused shadow: `box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4)`.
3.  **Level 2 (Overlays/Modals):** #242424. These elements sit higher, using a more pronounced shadow: `box-shadow: 0 12px 40px rgba(0, 0, 0, 0.6)`.

To maintain the "No-Line" philosophy, interactive elements like buttons use a slight luminosity increase on hover rather than an outline.

## Shapes
The shape language is "Rounded," providing a soft, approachable feel that balances the serious nature of the dark palette. 

- **Standard Components:** Buttons, inputs, and small chips use a 0.5rem (8px) radius.
- **Large Containers:** Cards and content modules use "rounded-lg" (1rem / 16px).
- **Feature Elements:** Images or large call-to-action sections use "rounded-xl" (1.5rem / 24px).

This consistency in curvature ensures that even without borders, the eye can easily distinguish separate objects through their soft-radius corners.

## Components

- **Buttons:** Primary buttons are Solid White with Black text. Secondary buttons are Tonal (#242424) with White text. There are no outlines. On hover, primary buttons drop opacity to 90%, and secondary buttons shift to #2A2A2A.
- **Input Fields:** Backgrounds are set to #181818 (matching Level 1 elevation). Focus is indicated by a subtle increase in background luminosity or a primary-colored cursor—never a border.
- **Cards:** Cards use the #181818 surface. Imagery within cards should have the same corner radius as the container (1rem). 
- **Lists:** Items are separated by whitespace and tonal shifts rather than horizontal rules.
- **Chips:** Small, pill-shaped elements with #242424 backgrounds and #A0A0A0 text, used for categorization.
- **Checkboxes & Radios:** Use Solid White for the "checked" state to provide a crisp, high-contrast focal point against the dark background.