# WP Easy

A framework for building modern WordPress themes—without the hassle of modern frontend build tools.

---

## Table of Contents

- [Features](#features)
- [Why WP Easy?](#why-wp-easy)
- [Getting Started](#getting-started)
- [Router](#router)
- [Templates & Components](#templates--components)
  - [Single File Components (SFC)](#single-file-components)
  - [Layouts](#layouts)
  - [Templates](#templates)
  - [Components](#components)
- [Styles & Scripts](#styles--scripts)
  - [Global Styles & Scripts](#global-styles--scripts)
  - [SCSS Support](#scss-support)
  - [JavaScript](#javascript)
  - [Caching](#caching)
- [Helper Functions](#helper-functions)
- [SVG Usage](#svg-usage)
- [Live Reload](#live-reload)

---

## Features

- **No build step:** Just code, save, and refresh—no npm or terminal required.
- **Flexible template routing:** Easily map URLs to templates and layouts, or fall back to WordPress's default template hierarchy.
- **Component-based architecture:** Build your site using reusable templates and components.
- **Single File Components (SFC):** Combine PHP, HTML, SCSS, and JS in a single file for layouts, templates, and components.
- **SCSS support:** Write modern, nested styles directly in your components and templates.
- **Automatic CSS & JS loading:** All stylesheets and scripts in the appropriate directories are auto-enqueued.
- **SVG support:** Easily include and customize SVGs in your theme, and enable SVG upload in the WordPress media library.
- **Live reload:** Instantly see changes in your browser during development.
- **Performance optimizations:** Automatic caching for styles and scripts, and JS/CSS source maps for easier debugging.
- **Opinionated structure:** Encourages best practices and maintainable code organization.
- **Advanced Custom Fields (ACF) integration:** Additional rules and helpers for ACF users.
- **Other enhancements:**
  - SEO & Open Graph best practices and meta tags included by default.
  - Font loading: Supports local fonts, Adobe, Google, and more.
  - Disable WordPress emojis for cleaner markup.
  - Customize login page header URL, text, and styling.
  - Disable comments if desired.

---

## Why WP Easy?

WP Easy is designed for people who understand the basics of HTML, CSS, and maybe a bit of JavaScript, but don't want to deal with the complexity of modern frontend development. There is no build step, no need to use the terminal, and you don't need to know what npm is—just code, save, and refresh.

The inspiration for this framework came from my brother, an amazing graphic designer who wanted to build WordPress themes using only his FTP-based code editor. He knows HTML and CSS really well, and some jQuery, but not modern JavaScript. In my experience, this is common for people whose jobs are tangential to frontend web development—designers, copywriters, project managers, and backend engineers.

My brother wants to build websites the "right" way, but doesn't want to deal with the mess of modern build tools. He wants to learn and improve, so I created a framework that nudges him in a more modern direction: component-based architecture, JS modules, SCSS, and template routing. WP Easy lets people like him build professional, modern themes without the usual barriers—just code with your favorite editor and see the results instantly.

---

## Getting Started

Follow these steps to get up and running with WP Easy:

1. **Set up your WordPress site as usual.**
2. **Install the WP Easy plugin.**
   - Upload or clone this plugin into your `wp-content/plugins` directory and activate it from the WordPress admin.
3. **Install the [WP Easy Theme](https://github.com/drewbaker/wp-easy-theme/).**
   - Download or clone the starter theme into your `wp-content/themes` directory and activate it.
4. **Enable [Pretty Permalinks](https://wordpress.org/documentation/article/customize-permalinks/#pretty-permalinks).**
   - Go to WordPress Settings > Permalinks and select any option other than "Plain".
5. **Configure your routes in `/router.php` in your theme.**
   - Map URLs to templates and layouts as described in the Router section below.

You're now ready to start building your site using WP Easy's component-based approach!

---

## Router

The router is the heart of WP Easy's template system. It lets you map URLs to specific templates and layouts, giving you full control over your site's structure—similar to routing in modern JavaScript frameworks.

### Where to Configure

Define your site's routes in your theme's `/router.php` file.  
If `router.php` is missing, the theme will follow WordPress's default template hierarchy.

### Example

```php
$routes = [
    'home'        => '/',
    'work'        => '/work/',
    'work-detail' => [
        'path'    => '/work/:spot/',
        'layout'  => 'alternate',
        'template'=> 'work'
    ],
    'reel'        => '/reel/',
];
return $routes;
```

### How Routing Works

- **Route key:** The array key (e.g., `home`, `work`) is the route name.
- **Path:** The value can be a string (for simple routes) or an array (for advanced options).
- **Array syntax:**
  - `path` — The URL pattern to match (supports parameters like `:spot`).
  - `layout` (optional) — The layout file to use from `/layouts/`. Defaults to `default.php` if not set.
  - `template` (optional) — The template file to use from `/templates/`. If not set, the route key is used as the template name.

This syntax is similar to Node's Express path syntax. For simple routes, you can use `'name' => '/path/'`. For more control, use the array syntax.

### Routing Fallback

If no route matches, or if `/router.php` is missing, WP Easy will fall back to WordPress's default template hierarchy. This ensures compatibility with standard WordPress behavior.

### Tips

- Use route parameters (e.g., `:spot`) to create dynamic routes.
- You can specify a custom layout or template for any route.
- Keep your routing file organized for easier maintenance.

---

## Templates & Components

WP Easy uses a Single File Component (SFC) structure for layouts, templates, and components. This means you can combine PHP, HTML, SCSS, and JavaScript in a single `.php` file, making your code more modular and maintainable.

### How SFCs Work

Each SFC can contain the following blocks:
- **PHP:** For logic and data preparation (at the top of the file).
- **<head> (optional):** For including third-party stylesheets or JS snippets.
- **<template>:** The main HTML markup for your layout, template, or component.
- **<style>:** Scoped SCSS/CSS for this file.
- **<script>:** JavaScript specific to this file.

**Example SFC:**
```php
<?php // PHP logic ?>
<head>
    <!-- Head content here -->
</head>

<template>
    <div class="example">Some HTML here</div>
</template>

<style>
.example { color: red; }
</style>

<script>
$('.example').click(function() {
    // JS here
});
</script>
```

---

### Layouts

- Located in `/layouts`
- Use the SFC structure as above.
- Can define layout-specific CSS and JS inside the layout file.
- Use `use_outlet()` to load the template file (like a slot in other frameworks).
- Can load header and footer components using `use_component()`.
- The `<head>` block can be added before `<template>` to load third-party stylesheets or JS.

---

### Templates

- Located in `/templates`
- Use the SFC structure.
- Represent the main content for a route.
- Can include components using `use_component()`.

**Example Template:**
```php
<template>
<main class="work">
    <?php use_component('work-block', ['title' => 'Test title']); ?>
</main>
</template>
```

---

### Components

- Located in `/components`
- Use the SFC structure.
- Should be isolated and only use data passed via props.
- Each component can have its own `<style>` or `<script>` block.

**Example Component:**
```php
<?php
$args = set_defaults($args, ['title' => 'Default Title']);
?>
<template>
    <div class="work-block">
        <h2><?= $args['title']; ?></h2>
    </div>
</template>
<style>
.work-block { background: red; }
</style>
```

---

**Key Points:**
- SFCs keep logic, markup, styles, and scripts together for each piece of your site.
- Layouts, templates, and components all use the same SFC pattern.
- Use helper functions like `use_component()`, `use_outlet()`, and `set_defaults()` to keep your code clean and modular.

---

## Styles & Scripts

WP Easy makes managing styles and scripts simple and modern, with support for SCSS, modular JavaScript, and automatic asset loading.

### Key Features

- **SCSS support:** Write modern, nested styles in your SFCs and global files.
- **Component Styles & Scripts:** Use `<style>` and `<script>` blocks directly in your SFC files (layouts, templates, and components).
- **Site Styles & Scripts:** All files in `/styles/` and `/scripts/` are automatically loaded.
- **Automatic Caching:** Styles and scripts are cached for optimal performance.
- **Source Maps:** CSS and JS source maps are enabled for all files, including templates, for easier debugging.
- **jQuery:** jQuery is enqueued by default, and `$` is available as a synonym for `jquery`.

---

### SCSS Support

- SCSS syntax is supported in `<style>` blocks within SFCs.
- Global SCSS files in `/styles/global/` are automatically imported—no need to use `@import` in every SFC.
- **Tip:** Namespace your CSS under a class matching the file or component name for better maintainability.

**Example:**
```scss
<style>
.example {
    background: red;
    .title {
        color: blue;
    }
    @media #{$lt-phone} {
        background-color: yellow;
        .title {
            color: var(--color-black);
        }
    }
}
</style>
```

---

### Styles in `/styles/` Directory

- **Global styles:** SCSS files in `/styles/global/` are auto-imported into all SFC and site styles.
- **Site styles:** SCSS files in `/styles/` are merged, compiled, and saved as `general-compiled(.min).css`. The compiled file is automatically loaded.
- **CSS auto-enqueue:** All CSS files in `/styles/` are automatically enqueued.
- **Custom login page styles:** Place in `/styles/login.css`.
- **Custom admin styles:** Place in `/styles/admin.css`.

---

### Scripts in `/scripts/` Directory

- **Component JS as modules:** Component JS files are enqueued as ES modules using `wp_enqueue_script_module()`, not inline, enabling [importmap](https://developer.mozilla.org/en-US/docs/Web/HTML/Reference/Elements/script/type/importmap).
- **Main script file:** `/scripts/main.js` is the entry point for site-wide scripts.
- **Utility scripts:** All JS files in `/scripts/` and `/scripts/utils/` are enqueued as modules and set as dependencies of the main script.
- **Library scripts:** All scripts in `/scripts/libs/` are auto-enqueued as modules.

---

**Key Points:**
- Styles and scripts are modular, auto-loaded, and optimized for performance.
- Use SFCs to keep your styles and scripts close to your markup and logic.
- Take advantage of global and site-wide files for shared styles and functionality.

---

## Helper Functions

WP Easy provides a set of global helper functions to make building templates and components easier, more modular, and more maintainable. These helpers streamline common tasks such as rendering components, managing layouts, handling SVGs, and working with arguments.

### Routing & Layout

- **get_route_name()**
  - Returns the current active route's name.
  - *Usage:* `if (get_route_name() === 'home') { /* ... */ }`
- **use_layout()**
  - Renders the current layout file. Layout is determined by router file. The fallback layout name is 'default'. Typically used internally in theme's `template.php`, but can be called to force a layout render.
- **use_outlet()**
  - Outputs the child content inside a layout (similar to a slot in other frameworks).
- **use_children( $args = [] )**
  - Returns child posts of current post, passing optional wp_query arguments.

### Components

- **use_component( $name, $props = null )**
  - Renders a component by name, passing optional props/arguments.
  - *Usage:* `use_component('work-block', ['title' => 'Test title']);`

### SVGs

- **use_svg( $name, $args = [] )**
  - Renders an inline SVG from the `/images` directory, with optional attributes (like class, width, etc).
  - *Usage:* `use_svg('logo', ['class' => 'header-logo']);`

### Arguments & Attributes

- **set_defaults( $args, $defaults )**
  - Merges user-supplied arguments with default values for components or templates.
  - *Usage:* `$args = set_defaults($args, ['title' => 'Default Title']);`
- **set_attribute( $att_name, $condition )**
  - Conditionally adds an attribute to an HTML element if the condition is true.
  - *Usage:* `set_attribute('disabled', !$is_enabled);`

---

These helpers are available globally in your templates, components, and layouts, making it easy to build dynamic, maintainable WordPress themes with WP Easy.


## SVG Usage

Use `use_svg('logo', ['class' => 'foo'])` to render `/images/logo.svg` with custom attributes.

**Example Output:**

```html
<svg class="foo" ...>
    <!-- SVG contents here -->
</svg>
```

---

## Live Reload

WP Easy includes a built-in live reload feature that automatically refreshes your browser when you make changes to theme files—similar to hot reload in modern development frameworks like Vue.js.

### How It Works

When `WP_DEBUG` is enabled in your WordPress configuration, the live reload module automatically:

- **Monitors theme files** for changes in real-time
- **Refreshes the browser** instantly when files are modified
- **Supports child themes** by monitoring both parent and child theme directories
- **Works with all file types** including PHP, SCSS, CSS, and JavaScript files

### Setup

1. **Enable WordPress Debug Mode**
   
   Add this to your `wp-config.php`:
   ```php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **That's it!** The live reload feature will automatically activate when you're logged in as an administrator.

### What Gets Monitored

The live reload feature watches for changes in all theme/child-theme sub directories excluding:
- `/node_modules` — Node.js dependencies (not theme files)
- `/.git` — Version control files
- `/vendor` — Composer dependencies
- `/images` — Static assets (changes don't require page refresh)

### Features

- **Instant feedback** — See changes immediately without manual refresh
- **Child theme support** — Works with both parent and child themes

### Troubleshooting

**Live reload not working?**
- Ensure `WP_DEBUG` is set to `true` in `wp-config.php`
- Verify your theme files are in the correct directories

**Browser console errors?**
- Live reload errors are logged to the browser console but don't affect functionality
- These are typically connection-related and resolve automatically

### Disabling Live Reload

To disable live reload in production:
```