# Design System Specification: The Tactile Editorial

## 1. Overview & Creative North Star: "The Digital Curator"

This design system moves away from the sterile, flat nature of traditional SaaS interfaces toward a "Digital Curator" aesthetic. It balances the precision of high-end editorial design with the physical intuition of "Skeumorphic Softness." 

The goal is to create an interface that feels less like a software tool and more like a physical object—a premium remote or a bespoke control panel crafted from satin-finish polymer. We achieve this by breaking the rigid grid through intentional asymmetry, generous white space, and a reliance on tonal depth rather than structural lines. The interface doesn't just display data; it presents it with a sense of "weight" and "presence."

---

## 2. Colors & Surface Logic

Our palette is built on a foundation of "Off-Whites" and "Soft Grays," punctuated by high-contrast `primary` (#000000) accents. This creates an authoritative, premium feel.

### The "No-Line" Rule
**Explicit Instruction:** Do not use 1px solid borders to section content. Boundaries must be defined solely through background color shifts. Use `surface-container-low` (#f3f3f3) sections sitting on a `surface` (#f9f9f9) background to create natural separation.

### Surface Hierarchy & Nesting
Treat the UI as a series of physical layers. Use the surface-container tiers to define importance:
- **Surface (Base):** #f9f9f9 – The foundational canvas.
- **Surface-Container-Low:** #f3f3f3 – Tertiary grouping areas.
- **Surface-Container-Highest:** #e2e2e2 – Recessed elements (like slider tracks or inactive states).
- **Surface-Container-Lowest:** #ffffff – For "raised" cards that need to pop against the gray base.

### The "Glass & Gradient" Rule
For floating modals or global navigation, use `surface_container_lowest` at 80% opacity with a `backdrop-filter: blur(20px)`. Main CTAs should utilize a subtle linear gradient from `primary` (#000000) to `primary_container` (#3c3b3b) to provide a "sheen" that flat black cannot achieve.

---

## 3. Typography: Editorial Authority

We use a dual-typeface system to create an editorial rhythm.

*   **Display & Headlines (Manrope):** Chosen for its geometric precision and modern warmth. Use `display-lg` (3.5rem) for hero data points (like temperatures or main metrics) to create a clear focal point.
*   **Body & Labels (Inter):** The workhorse. Inter provides maximum legibility at small sizes. `label-md` (0.75rem) should be used for metadata and status, often in `on_surface_variant` (#474747) to reduce visual noise.

**Hierarchy Tip:** Contrast a `display-sm` value with a `label-sm` unit (e.g., 22°C) to create a sophisticated, high-end "information cluster."

---

## 4. Elevation & Depth: Tonal Layering

Traditional drop shadows are largely replaced by **Tonal Layering**. Depth is a result of how light interacts with surfaces.

*   **The Layering Principle:** Place a `surface-container-lowest` (#ffffff) card on a `surface-container-low` (#f3f3f3) background. The 4% lightness shift creates a "soft lift" that feels organic.
*   **Ambient Shadows:** For high-priority floating elements, use extra-diffused shadows. 
    *   *Shadow Logic:* `0px 20px 40px rgba(0, 0, 0, 0.04)`. The shadow must feel like ambient occlusion, not a dark smudge.
*   **The "Ghost Border" Fallback:** If accessibility requires a stroke, use `outline_variant` (#c6c6c6) at 20% opacity. **Forbid 100% opaque borders.**
*   **Tactile Insets:** For tracks (sliders, toggles), use a "sunken" effect by applying a subtle inner shadow or simply using `surface_dim` (#dadada) against a lighter surface.

---

## 5. Components

### Cards
*   **Style:** No borders. `border-radius: 2rem (lg)`.
*   **Interaction:** On hover, transition the background from `surface_container_lowest` to a slightly more luminous white, or increase the Ambient Shadow spread.
*   **Content:** Use vertical whitespace (32px+) to separate headers from content rather than dividers.

### Toggle Switches & Dials
*   **Toggles:** The track should be `surface_container_highest` (#e2e2e2) with an inset feel. The "knob" is `primary` (#000000) when active, creating a high-contrast focal point.
*   **Circular Sliders/Dials:** Mimic a physical knob. Use a conic gradient or a stacked series of circular shapes with varying `surface` tones to create a 3D "extruded" effect.

### Buttons
*   **Primary:** Solid `primary` (#000000) with `on_primary` (#e5e2e1) text. Large `xl` (3rem) corner radius.
*   **Icon-based:** Use `surface_container_high` (#e8e8e8) for the base. Icons should be 24px, centered. The button should feel like a physical, tactile "key."

### Sidebar Navigation
*   **Style:** Asymmetric. The sidebar should be a clean `surface` (#f9f9f9) or `surface_container_lowest` (#ffffff) column. 
*   **Active State:** Avoid "blocks" of color. Use a high-contrast `primary` pill or a subtle "indicator dot" next to the label.

### Linear Sliders
*   **Track:** `surface_container_highest`. 
*   **Fill:** `primary`.
*   **Handle:** A large, `surface_container_lowest` circle with an Ambient Shadow, making it appear "grabable."

---

## 6. Do’s and Don'ts

### Do:
*   **Do** embrace negative space. If a layout feels crowded, increase the margin-bottom on your headlines.
*   **Do** use "Optical Centering." Sometimes a tactile icon needs to be 1px higher than the mathematical center to look "right."
*   **Do** use `title-lg` for card titles to maintain an editorial hierarchy.

### Don’t:
*   **Don't** use dividers (`<hr>`). Separate logical groups with 48px or 64px of empty space.
*   **Don't** use pure blue for "links." Interaction is signified by proximity, icon cues, or subtle shifts in surface tone.
*   **Don't** use sharp corners. Every element should feel "tumbled" and soft to the touch (minimum radius `sm`: 0.5rem).
*   **Don't** use high-saturation colors for status. Use `error` (#ba1a1a) sparingly, and prioritize the neutral palette for a calm user experience.