# WP Easy

A framework for building modern WordPress themes—without the hassle of modern frontend build tools.

---

## Table of Contents

- [Features](#features)
- [Why WP Easy?](#why-wp-easy)
- [Getting Started](#getting-started)
- [Router](#router)
- [Single File Components](#single-file-components)
- [Layouts](#layouts)
- [Templates & Components](#templates--components)
- [Global Styles & Scripts](#global-styles--scripts)
- [SCSS Support](#scss-support)
- [SVG Usage](#svg-usage)
- [JavaScript](#javascript)
- [Fonts](#fonts)
- [Caching](#caching)
- [Helper Functions](#helper-functions)

---

## Features

- **No build step:** Just code, save, and refresh—no npm or terminal required.
- **Component-based architecture:** Build your site using reusable templates and components.
- **Flexible template routing:** Easily map URLs to templates and layouts.
- **Single File Components (SFC):** Combine PHP, HTML, SCSS, and JS in one file for templates and components.
- **SCSS support:** Write modern, nested styles directly in your components and templates.
- **Automatic CSS & JS loading:** All stylesheets and scripts in the appropriate directories are auto-enqueued.
- **SVG support:** Easily include and customize SVGs in your theme.
- **Font loading:** Supports local fonts, Adobe, Google, and more.
- **SEO & Open Graph ready:** Best practices and meta tags included by default.
- **Opinionated structure:** Encourages best practices and maintainable code organization.
- **Advanced Custom Fields (ACF) integration:** Additional rules and helpers for ACF users.

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

### Example

```php
$routes = [
    'home'        => '/',
    'work'        => '/work/',
    'work-detail' => ['path' => '/work/:spot/', 'layout' => 'alternate', 'template' => 'work'],
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

### Helper Functions

- `get_route_name()` — Returns the current active route's name. Useful for conditional logic in your templates or layouts.

With the router, you can easily create custom page structures and layouts for any URL your site needs.

---

## Single File Components

Inspired by Vue SFCs, WP Easy allows you to combine PHP, HTML, CSS (SCSS), and JS in a single `.php` file.

**Example:**

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

## Layouts

- Located in `/layouts`
- Not SFCs—put global styles in `main.scss` and scripts in `main.js`
- Must include required functions (see [starter theme](https://github.com/drewbaker/wp-easy-theme))

---

## Templates & Components

- **Templates**: Located in `/templates`
- **Components**: Located in `/components`
- Both are `.php` files using SFC syntax.

**Example Template:**

```php
<template>
<main class="work">
    <?php use_component('work-block', ['title' => 'Test title']); ?>
</main>
</template>
```

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

- Keep components isolated: only use data passed via props.
- Each component can have its own `<style>` or `<script>` block.

---

## Global Styles & Scripts

- Use `<style>` and `<script>` blocks in templates/components.
- Global styles: `/styles/main.scss`
- Global scripts: `/scripts/main.js`
- All files in `/styles/*` are auto-loaded.

---

## SCSS Support

- SCSS syntax is supported in `<style>` blocks.
- Global SCSS variables: `/styles/global/`
- **Tip:** Namespace CSS under a class matching the file name.

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

## SVG Usage

Use `use_svg('logo', ['class' => 'foo'])` to render `/images/logo.svg` with custom attributes.

**Example Output:**

```html
<svg class="foo" ...>
    <!-- SVG contents here -->
</svg>
```

---

## JavaScript

- All scripts in `/scripts/libs/` are auto-enqueued as modules.
- jQuery `$`