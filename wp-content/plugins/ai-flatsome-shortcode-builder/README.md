# AI Flatsome Shortcode Builder

**Version:** 1.0.0  
**Requires WordPress:** 5.9+  
**Requires PHP:** 7.4+

> **⚠️ Important:** Always review the generated shortcode before publishing a page. AI output may need manual refinement.

---

## 📋 Table of Contents

1. [Installation](#installation)
2. [Configuration – OpenAI API Key](#configuration)
3. [Generating a Layout Shortcode](#generating)
4. [Creating a Draft Page](#creating-a-draft-page)
5. [How CSS is Handled](#how-css-is-handled)
6. [Tips & Recommendations](#tips)
7. [Next Version Improvements](#improvements)

---

## Installation

1. Download or clone this plugin folder (`ai-flatsome-shortcode-builder/`) into your WordPress plugins directory:
   ```
   wp-content/plugins/ai-flatsome-shortcode-builder/
   ```
2. Log in to your WordPress admin panel.
3. Go to **Plugins → Installed Plugins**.
4. Find **AI Flatsome Shortcode Builder** and click **Activate**.

---

## Configuration

### Enter your OpenAI API Key

1. In the WordPress admin sidebar, click **AI Flatsome**.
2. Click the **⚙️ Settings** tab.
3. Paste your **OpenAI API Key** into the *API Key* field.
   - Get your key at [platform.openai.com/api-keys](https://platform.openai.com/api-keys).
   - Your key is stored securely in the WordPress database and is never exposed on the frontend.
4. Select a **Model**:
   - **gpt-4o** *(recommended for image layout analysis)* — best accuracy.
   - **gpt-4o-mini** — faster and cheaper, good for URL analysis.
   - **Custom** — enter any model name manually (e.g. `gpt-4-turbo`).
5. Set **Temperature** (default `0.2`): lower values produce more consistent JSON output.
6. Click **💾 Save Settings**.

---

## Generating a Layout Shortcode

### Mode 1: Image to Layout

1. Go to the **⚡ Generate** tab.
2. Select **🖼️ Image to Layout**.
3. Drag and drop a UI screenshot onto the upload area, or click to browse.
   - Accepted formats: JPEG, PNG, GIF, WebP
   - Maximum size: 8 MB
4. Click **✨ Generate Layout**.
5. Wait up to ~30 seconds for the AI to analyze the image.
6. The following outputs will appear:
   - **JSON Layout** — raw AI output (for reference).
   - **Flatsome Shortcode** — ready to paste into Flatsome UX Builder.
   - **Generated CSS** — scoped CSS for this layout.

### Mode 2: URL to Layout

1. Select **🌐 URL to Layout**.
2. Enter a full URL (must start with `http://` or `https://`).
3. Click **✨ Generate Layout**.
4. The plugin fetches the page HTML, strips scripts/styles, and sends the text to AI for analysis.

### Copying Output

- Click **📋 Copy** next to *Flatsome Shortcode* to copy it to clipboard.
- Click **📋 Copy** next to *Generated CSS* to copy the CSS.

---

## Creating a Draft Page

1. After generating a layout, enter a **Page Title** in the input box below the outputs.
2. Click **🚀 Create Draft Page**.
3. The plugin will:
   - Create a new WordPress **Draft** page with the shortcode as content.
   - Save the generated CSS into the page's post meta (`_ai_flatsome_generated_css`).
   - Return a link to **edit the page** in the WordPress admin.
4. Open the edit link, review the shortcode in Flatsome UX Builder, adjust as needed, then **Publish**.

> **Note:** The generated CSS is automatically injected as inline CSS only when that specific page is viewed — it does **not** affect the rest of your website.

---

## How CSS is Handled

The plugin generates two types of CSS:

| Type | Description |
|------|-------------|
| **Reusable Component CSS** | Classes like `.ai-button`, `.ai-product-card` used across sections |
| **Section Scoped CSS** | CSS scoped to each section's unique class (e.g. `.ai-section-hero-001`) |

All CSS is stored per-page in post meta and loaded only on that page's frontend, preventing global style conflicts.

---

## Tips & Recommendations

- 🎯 Use **gpt-4o** for image-based layout analysis — it produces significantly better results than gpt-4o-mini.
- 📸 Use **clean, high-resolution screenshots** (1280px+ width) for best accuracy.
- 🔍 For URL mode, the plugin extracts visible text content, so it works best on **content-heavy pages** (not single-page apps).
- ✏️ Always **review and tweak** the generated shortcode in Flatsome UX Builder before publishing.
- 🎨 If the AI-generated CSS conflicts with your Flatsome theme, check that all classes are properly scoped.
- ⚡ For landing pages with complex animations, add custom JS manually — the plugin generates JS only when strictly necessary.

---

## Next Version Improvements

The following enhancements are planned for version 1.1.0+:

1. **Layout Library** — Save generated layouts to a local library for reuse.
2. **One-click Publish** — Option to publish the page directly instead of creating a draft.
3. **Revision History** — View and restore previous AI-generated layouts.
4. **Figma/XD Import** — Accept Figma JSON or Adobe XD exports as input.
5. **Shortcode Preview** — Render a live preview of the shortcode directly in the admin UI using an iframe.
6. **Multi-page Support** — Analyze multiple screenshots/URLs and combine them into one layout.
7. **CSS Custom Properties** — Generate CSS variables for colors and fonts for easier theming.
8. **Claude / Gemini Support** — Alternative AI provider support beyond OpenAI.
9. **Flatsome Global Sections** — Option to save output as a Flatsome Global Section.
10. **WooCommerce Smart Products** — Detect product grids and pre-fill WooCommerce category/tag filters.

---

## License

GPLv2 or later. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
