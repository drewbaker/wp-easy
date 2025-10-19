# WP-Easy Theme Development Guide

A comprehensive guide for building modern WordPress themes using the WP-Easy framework—without the complexity of modern build tools.

---

## Table of Contents

- [What is WP-Easy?](#what-is-wp-easy)
- [Getting Started](#getting-started)
- [Theme Structure](#theme-structure)
- [Router System](#router-system)
- [Single File Components (SFCs)](#single-file-components-sfcs)
- [Layouts](#layouts)
- [Templates](#templates)
- [Components](#components)
- [Styles & SCSS](#styles--scss)
- [Scripts & JavaScript](#scripts--javascript)
- [SVG Usage](#svg-usage)
- [Fonts](#fonts)
- [Hot Reload Development](#hot-reload-development)
- [Helper Functions](#helper-functions)
- [Post Object Extensions](#post-object-extensions)
- [Common Patterns](#common-patterns)

---

## What is WP-Easy?

WP-Easy is a framework designed for people who understand HTML, CSS, and JavaScript but want to build modern WordPress themes without dealing with complex build tools. There's no npm, no terminal commands, no webpack—just code, save, and refresh.

**Key Features:**
- **No build step required** - Just code and refresh
- **Component-based architecture** - Build reusable UI components
- **Single File Components** - Combine PHP, HTML, SCSS, and JS in one file
- **Automatic asset loading** - Styles and scripts are auto-enqueued
- **Live reload** - See changes instantly during development
- **SCSS support** - Write modern, nested styles
- **Flexible routing** - Map URLs to templates and layouts

---

## Getting Started

### 1. Install WP-Easy Plugin
Upload the WP-Easy plugin to your `wp-content/plugins` directory and activate it.

### 2. Create Your Theme
Create a new theme directory in `wp-content/themes/your-theme-name/` with this basic structure:

**Check out the [WP-Easy Theme](https://github.com/drewbaker/wp-easy-theme/) starter theme.**

```
your-theme/
├── index.php
├── functions.php
├── router.php
├── template.php
├── style.css
├── layouts/
│   └── default.php
├── templates/
├── components/
├── styles/
├── scripts/
└── images/
```

### 3. Enable Pretty Permalinks
Go to WordPress Settings > Permalinks and select any option other than "Plain".

### 4. Configure Routes
Set up your routes in `router.php` to map URLs to templates and layouts.

---

## Theme Structure

### Core Files

**`router.php`** - Entry point that determines routing
```php
<?
$routes = [
    'home' => '/',
    'work' => '/work/',
    'work-detail' => [
        'path' => '/work/:slug/',
        'layout' => 'default',
        'template' => 'work-detail'
    ]
];
return $routes;
```

### Core Directories

**`/layouts/`** - Layout files that wrap your page content
- **Purpose**: Provide the overall page structure (header, footer, navigation)
- **Usage**: Wraps templates with common page elements
- **Example**: `default.php` contains header, main content area, and footer
- **Key function**: Usesd for different site wide layout (such as conditonal header or footer, logged in chrome etc.)

**`/templates/`** - Page-level templates for different routes
- **Purpose**: Define the main content for each page/route
- **Usage**: Contains the specific content for home, blow, about pages, etc.
- **Example**: `home.php` for homepage, `work-detail.php` for work pages
- **Key function**: Uses `use_component()` to build pages from reusable pieces

**`/components/`** - Reusable UI components, loaded using `use_component()`
- **Purpose**: Build modular, reusable pieces of your site
- **Usage**: Header, footer, buttons, image blocks, etc.
- **Example**: `header.php`, `work-block.php`, `wp-image.php`
- **Key function**: Accept props via `$args` and render specific UI elements

**`functions/utils.php`** - Your theme functions would go in here.
- **`/styles/`** - Global SCSS/CSS files (auto-loaded)
- **`/scripts/`** - JavaScript files (auto-loaded)
- **`/images/`** - Static images and SVGs. SVG's loaded using `use_svg()`

---

## Router System

The router is the heart of WP-Easy's template system. It's the entry point that determines which layout and template to use for each URL. Define your routes in `router.php`:

```php
<?
$routes = [
    'home'        => '/',
    'work'        => '/work/',
    'work-detail' => [
        'path'     => '/work/:slug/',
        'layout'   => 'default',
        'template' => 'work-detail'
    ],
    'about'       => '/about/',
];
return $routes;
```

### Router Syntax

**Simple Routes:**
```php
'home' => '/',           // Maps to /templates/home.php
'work' => '/work/',       // Maps to /templates/work.php
```

**Advanced Routes:**
```php
'work-detail' => [
    'path'     => '/work/:slug/',    // URL pattern with parameter
    'layout'   => 'alternate',       // Use /layouts/alternate.php
    'template' => 'work-detail'     // Use /templates/work-detail.php
]
```

### Route Parameters
Use `:parameter` syntax for dynamic routes:
- `:slug` - Post slug
- `:id` - Post ID
- `:page` - Page number

These parameters aren't used for anything currently, it's simply for human readability. 

### How a request leads to a built page

1. **Router** (`router.php`) - Determines which layout and template to use
2. **Layout** (`/layouts/default.php`) - Wraps the content with header, footer, etc.
3. **Template** (`/templates/home.php`) - Contains the main page content
4. **Components** - Reusable pieces used within templates (and in layouts or inside other components too!)

### Fallback Behavior
If no route matches, WP-Easy falls back to the theme's `/index.php` file.

---

## Single File Components (SFCs)

SFCs allow you to combine PHP logic, HTML markup, SCSS styles, and JavaScript in a single `.php` file.

### SFC Structure

```php
<?
// PHP logic at the top
$args = set_defaults($args, ['title' => 'Default Title']);
?>

<head>
    <!-- Optional tag: Third-party stylesheets or scripts that should go in the head-->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<template>
    <!-- Your HTML markup -->
    <div class="component">
        <h2><?= $args['title']; ?></h2>
    </div>
</template>

<style>
    /* SCSS/CSS styles */
    .component {
        background: red;
        .title {
            color: blue;
        }
    }
</style>

<script>
    // JavaScript for this component
    $('.component').click(function() {
        // jQuery is available as $()
    });
</script>
```

### SFC Blocks Explained

- **PHP Block** - Logic and data preparation
- **`<head>` Block** - Third-party stylesheets/scripts
- **`<template>` Block** - HTML markup
- **`<style>` Block** - Scoped SCSS/CSS
- **`<script>` Block** - Component-specific JavaScript

---

## Layouts

Layouts wrap your page content and provide the overall page structure.

### Default Layout (`/layouts/default.php`)

```php
<?
$header_size = 'normal';
$show_footer = true;

switch (use_route_name()) {
    case 'work-detail':
        $header_size = 'small';
        $show_footer = false;
        break;
}
?>

<template>
    <? use_component('header', ['size' => $header_size]); ?>
    
    <main id="content">
        <? use_outlet(); ?>
    </main>
    
    <? if ($show_footer) : ?>
        <? use_component('footer'); ?>
    <? endif; ?>
</template>

<style>
    /* Layout-specific styles */
    #content {
        min-height: 100vh;
    }
</style>
```

### Layout Features

- **`use_outlet()`** - Renders the current template
- **`use_component()`** - Includes header, footer, or other components
- **Route-based logic** - Different layouts for different pages
- **Scoped styles** - Layout-specific CSS

---

## Templates

Templates represent the main content for each route.

### Basic Template (`/templates/home.php`)

```php
<template>
    <main class="template-home">
        <? use_component('hero-section'); ?>
        
        <section class="featured-work">
            <? foreach (use_children() as $post) : ?>
                <? use_component('work-block', [
                    'title' => $post->title,
                    'url' => $post->url,
                    'image_id' => $post->thumbnail_id
                ]); ?>
            <? endforeach; ?>
        </section>
    </main>
</template>

<style>
    .template-home {
        .featured-work {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
    }
</style>
```

### Template Features

- **Post data access** - Use `$post` object with extensions
- **Component composition** - Build pages from reusable components
- **Scoped styles** - Template-specific CSS
- **Helper functions** - Use `use_children()`, `use_posts()`, etc.

---

## Components

Components are reusable UI pieces that can be used across templates and layouts.

### Basic Component (`/components/work-block.php`)

```php
<?
$args = set_defaults($args, [
    'title' => '',
    'url' => '',
    'image_id' => 0,
    'class' => ''
]);
?>

<template>
    <article class="work-block <?= $args['class']; ?>">
        <a href="<?= esc_url($args['url']); ?>">
            <? use_component('wp-image', [
                'image_id' => $args['image_id'],
                'class' => 'work-image'
            ]); ?>
            
            <h3 class="work-title"><?= esc_html($args['title']); ?></h3>
        </a>
    </article>
</template>

<style>
    .work-block {
        position: relative;
        overflow: hidden;
        
        .work-image {
            transition: transform 0.3s ease;
        }
        
        &:hover .work-image {
            transform: scale(1.05);
        }
    }
</style>
```

### Component Best Practices

- **Use `set_defaults()`** - Set default values for props
- **Scoped styles** - Component-specific CSS
- **Reusable props** - Design for flexibility

---

## Styles & SCSS

WP-Easy provides powerful SCSS support with automatic compilation and loading.

### Global Styles (`/styles/main.scss`)

```scss
// Variables
$primary-color: #ff6b6b;
$font-primary: 'Helvetica', sans-serif;

// Mixins
@mixin button-style {
    padding: 1rem 2rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

// Global styles
body {
    font-family: $font-primary;
    line-height: 1.6;
}

.button {
    @include button-style;
    background: $primary-color;
    color: white;
}
```

### Component Styles in SFCs

```scss
<style>
    .component {
        background: var(--color-primary);
        
        .title {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        // Responsive design
        @media #{$lt-phone} {
            .title {
                font-size: 1.5rem;
            }
        }
    }
</style>
```

### Style Features

- **SCSS compilation** - Automatic SCSS to CSS conversion
- **Global imports** - Files in `/styles/global/` are auto-imported
- **Component scoping** - Styles are scoped to components
- **Media queries** - Use `$lt-phone`, `$lt-tablet` variables
- **CSS variables** - Use `var(--color-primary)` for theming

---

## Scripts & JavaScript

JavaScript files are automatically loaded from the `/scripts` folder and can be modular.

### Main Script (`/scripts/main.js`)

```javascript
// Site-wide JavaScript
$(document).ready(function() {
    // Initialize components
    initNavigation();
    initScrollEffects();
});

function initNavigation() {
    $('.hamburger').click(function() {
        $('.menu-tray').toggleClass('is-open');
    });
}

function initScrollEffects() {
    $(window).scroll(function() {
        if ($(window).scrollTop() > 100) {
            $('.header').addClass('is-scrolled');
        } else {
            $('.header').removeClass('is-scrolled');
        }
    });
}
```

### Scripts in SFCs

- **jQuery included** - `$` is available globally
- **ES modules** - Modern JavaScript module system
- **Auto-loading** - All files in `/scripts/` are loaded
- **Component scripts** - Scoped to specific components
- **No document.ready needed** - Component `<script>` blocks run when component is rendered.

```javascript
<script>
    // Component-specific JavaScript
    $('.work-block').hover(
        function() {
            $(this).find('.work-image').addClass('is-hovered');
        },
        function() {
            $(this).find('.work-image').removeClass('is-hovered');
        }
    );
</script>
```

### Component Isolation Best Practices

**✅ Good - Component stays isolated:**
```javascript
<script>
    // Only manipulate elements within this component
    $('.work-block').click(function() {
        $(this).toggleClass('is-expanded');
    });
</script>
```

**❌ Avoid - Don't manipulate other parts of the site:**
```javascript
<script>
    // Don't do this - affects other components
    $('.header').addClass('work-page');
    $('.footer').hide();
</script>
```

**✅ For site-wide functionality, use `/scripts/main.js` on use a Template or Layout `<script>` block**
```javascript
// /scripts/main.js - Site-wide functionality
$(document).ready(function() {
    // Global navigation, scroll effects, etc.
    initNavigation();
    initScrollEffects();
});
```

### ES Modules Support

WP-Easy automatically converts component scripts to ES modules:

```javascript
<script>
    // This becomes an ES module automatically
    export function initWorkBlock() {
        $('.work-block').click(function() {
            // Component logic here
        });
    }
</script>
```

**Module Features:**
- **Automatic conversion** - Component scripts become ES modules
- **Dependency management** - Modules can import/export functions
- **Performance** - Only loads when component is used
- **Isolation** - Component scripts don't conflict with each other

**Module Names:**
- **Component modules**: `{type}-{filename}` (e.g., `components-work-block`, `templates-home`)
- **Utility modules**: `utils-{filename}` (e.g., `utils-clamp`, `utils-delay`)
- **Main module**: `main` (site-wide scripts)

**Importing Between Modules:**
```javascript
// In a component script
<script>
    import { clamp } from 'utils-clamp';
    import { delay } from 'utils-delay';
    
    export function initWorkBlock() {
        $('.work-block').click(function() {
            const value = clamp(0, 100, 50); // From utils-clamp
            delay(1000).then(() => {
                // From utils-delay
            });
        });
    }
</script>
```

**Exporting from Components:**
```javascript
<script>
    // Export functions for other modules to use
    export function showWorkDetails() {
        $('.work-details').slideDown();
    }
    
    export function hideWorkDetails() {
        $('.work-details').slideUp();
    }
</script>
```

### Loading Custom JavaScript Libraries

Use the `<head>` tag to load third-party libraries that need to be available globally:

```php
<?
// Component that uses Chart.js
$args = set_defaults($args, ['chart_data' => []]);
?>

<head>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<template>
    <div class="chart-container">
        <canvas id="myChart"></canvas>
    </div>
</template>

<script>
    // Chart.js is now available globally
    const ctx = $('#myChart').get(0).getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: <?= json_encode($args['chart_data']); ?>
    });
</script>
```

**Common Use Cases:**
- **Analytics scripts** - Google Analytics, Facebook Pixel
- **Maps** - Google Maps, Mapbox
- **Charts** - Chart.js, D3.js
- **UI libraries** - Bootstrap, Foundation

---

## SVG Usage

WP-Easy makes it easy to include and customize SVGs.

### Basic SVG Usage

```php
<? use_svg('logo', ['class' => 'site-logo', 'width' => 120]); ?>
```

### SVG with Custom Attributes

```php
<? use_svg('icon-arrow', [
    'class' => 'arrow-icon',
    'width' => 24,
    'height' => 24,
    'fill' => 'currentColor'
]); ?>
```

### SVG Features

- **Automatic loading** - SVGs from `/images/` directory
- **Custom attributes** - Add classes, dimensions, etc.
- **Security** - Strips XML declarations and unwanted tags
- **Performance** - Inline SVGs for better performance

---

## Fonts

WP-Easy supports font loading using [Webfontloader](https://github.com/typekit/webfontloader).

### Font config (`/scripts/fonts.js`)

- **Local fonts** - Host fonts in your theme
- **Google Fonts** - Easy integration
- **Adobe Fonts** - Creative Cloud integration
- **Performance** - Optimized loading

See [Webfontloader](https://github.com/typekit/webfontloader) for explanation on how to load all the supported font types.

```javascript
// Load local fonts
WebFont.load({
    custom: {
        families: ['CustomFont'],
        urls: ['/fonts/custom-font.css']
    }
});
```

---

## Hot Reload Development

WP-Easy includes built-in hot reload for instant development feedback.

### Setup

1. **Enable WordPress Debug Mode**
   ```php
   // In wp-config.php
   define('WP_DEBUG', true);
   define('WP_DEBUG_LOG', true);
   ```

2. **That's it!** Hot reload automatically activates when you're logged in as an administrator.

### How It Works

- **File monitoring** - Watches for changes in theme files
- **Instant refresh** - Browser refreshes automatically
- **Child theme support** - Works with both parent and child themes
- **All file types** - PHP, SCSS, CSS, and JavaScript

### What Gets Monitored

- All theme subdirectories
- Excludes: `/node_modules`, `/.git`, `/vendor`, `/images`

### Troubleshooting

**Hot reload not working?**
- Ensure `WP_DEBUG` is set to `true`
- Check browser console for errors
- Verify you're logged in as administrator

---

## Helper Functions

WP-Easy provides powerful helper functions for common tasks.

### Routing & Layout

**`use_route_name()` or `get_route_name`** - Get current route name
```php
if (use_route_name() === 'home') {
    // Home page logic
}
```

### Components

**`use_component($name, $props)`** - Render component with props
```php
use_component('work-block', [
    'title' => 'Project Title',
    'url' => '/work/project/',
    'image_id' => 123
]);
```

### Data Helpers

**`use_children($args)`** - Get child pages of current page, ordered by menu_order
- **Args**: Standard `WP_Query` arguments with sensible defaults
- **Defaults**: `post_type: 'any'`, `post_parent: current_post_id`, `posts_per_page: -1`, `order: 'ASC'`, `orderby: 'menu_order'`
```php
<? foreach (use_children() as $post) : ?>
    <h3><?= $post->title; ?></h3>
<? endforeach; ?>

<? foreach (use_children(['post_type' => 'work', 'posts_per_page' => 6]) as $post) : ?>
    <h3><?= $post->title; ?></h3>
<? endforeach; ?>
```

**`use_posts($args)`** - Get posts for the current page, with pagination links also.
- **Args**: Standard `WP_Query` arguments with sensible defaults
- **Defaults**: `post_type: 'post'`, `posts_per_page: get_option('posts_per_page')`, `paged: current_page`, `orderby: 'date'`, `order: 'DESC'`
```php
<? 
$posts_data = use_posts();
foreach ($posts_data->posts as $post) : ?>
    <article><?= $post->title; ?></article>
<? endforeach; ?>

<? 
$work_posts = use_posts(['post_type' => 'work', 'posts_per_page' => 12]);
foreach ($work_posts->posts as $post) : ?>
    <article><?= $post->title; ?></article>
<? endforeach; ?>

<? if ($posts_data->next_posts_url) : ?>
    <a href="<?= $posts_data->next_posts_url; ?>">Next Page</a>
<? endif; ?>
```

**`use_adjacent($post_id, $direction)`** - Get next/previous page/post
```php
$next_post = use_adjacent($post->ID, 'next');
$prev_post = use_adjacent($post->ID, 'previous');
```

### SVGs

**`use_svg($name, $attrs)`** - Render SVG with attributes. `$name` is the filename of SVG file inside `/images/`.
```php
use_svg('logo', ['class' => 'header-logo', 'width' => 120]);
```

### Utilities

**`set_defaults($args, $defaults)`** - Set default values for a component's $args.
```php
$args = set_defaults($args, [
    'title' => 'Default Title',
    'class' => 'default-class'
]);
```

**`set_attribute($name, $condition)`** - Conditional HTML attributes
```php
<button <?= set_attribute('disabled', !$is_enabled); ?>>
    Submit
</button>
```

```php
<button <?= set_attribute('class="is-opened"', $is_opened); ?>>
    Close
</button>
```

---

## Post Object Extensions

WP-Easy extends the WordPress `$post` object with useful shortcuts.

### Available Extensions

**`$post->id`** - Post ID (same as `$post->ID`)
```php
echo $post->id; // 123
```

**`$post->url`** - Post permalink
```php
<a href="<?= $post->url; ?>">Read More</a>
```

**`$post->title`** - Filtered post title
```php
<h1><?= $post->title; ?></h1>
```

**`$post->content`** - Filtered post content
```php
<div class="content"><?= $post->content; ?></div>
```

**`$post->excerpt`** - Post excerpt, will return an auto excerpt if user generated one is empty.
```php
<p><?= $post->excerpt; ?></p>
```

**`$post->thumbnail_id`** - Featured image ID
```php
<? use_component('wp-image', [
    'image_id' => $post->thumbnail_id,
    'class' => 'featured-image'
]); ?>
```

### ACF Field Shortcuts

ACF (Advanced Custom Fields) fields are automatically available as shortcuts on the `$post` object:

```php
// If you have ACF fields like 'video_url', 'director_credit', 'gallery_images'
<? if ($post->video_url) : ?>
    <video src="<?= $post->video_url; ?>" controls></video>
<? endif; ?>

<? if ($post->director_credit) : ?>
    <p>Director: <?= $post->director_credit; ?></p>
<? endif; ?>

<? foreach ($post->gallery_images as $image) : ?>
    <? use_component('wp-image', [
        'image_id' => $image['id'],
        'class' => 'gallery-image'
    ]); ?>
<? endforeach; ?>
```

### Usage Examples

```php
// In a template
<article class="post">
    <h2><a href="<?= $post->url; ?>"><?= $post->title; ?></a></h2>
    
    <? if ($post->thumbnail_id) : ?>
        <? use_component('wp-image', [
            'image_id' => $post->thumbnail_id,
            'class' => 'post-image'
        ]); ?>
    <? endif; ?>
    
    <div class="excerpt"><?= $post->excerpt; ?></div>
</article>
```

---

## Common Patterns

### 1. Work/Portfolio Grid

```php
<template>
    <section class="work-grid">
        <? foreach (use_children() as $post) : ?>
            <article class="work-item">
                <a href="<?= $post->url; ?>">
                    <? use_component('wp-image', [
                        'image_id' => $post->thumbnail_id,
                        'class' => 'work-image'
                    ]); ?>
                    <h3><?= $post->title; ?></h3>
                </a>
            </article>
        <? endforeach; ?>
    </section>
</template>
```

### 2. Navigation Menu

```php
<template>
    <nav class="main-nav">
        <? wp_nav_menu([
            'menu_class' => 'nav-menu',
            'container' => false,
            'menu' => 'primary'
        ]); ?>
    </nav>
</template>
```

### 3. Blog Post List

```php
<template>
    <div class="blog-posts">
        <? 
        $posts_data = use_posts();
        foreach ($posts_data->posts as $post) : ?>
            <article class="blog-post">
                <h2><a href="<?= $post->url; ?>"><?= $post->title; ?></a></h2>
                <div class="excerpt"><?= $post->excerpt; ?></div>
            </article>
        <? endforeach; ?>
        
        <? if ($posts_data->next_posts_url) : ?>
            <a href="<?= $posts_data->next_posts_url; ?>" class="load-more">Load More</a>
        <? endif; ?>
    </div>
</template>
```

### 4. Image Gallery

```php
<template>
    <div class="gallery">
        <? foreach ($post->gallery_images as $image) : ?>
            <? use_component('wp-image', [
                'image_id' => $image['id'],
                'class' => 'gallery-image'
            ]); ?>
        <? endforeach; ?>
    </div>
</template>
```

### 5. Conditional Content

```php
<template>
    <div class="content">
        <? if ($post->video_url) : ?>
            <video src="<?= $post->video_url; ?>" controls></video>
        <? elseif ($post->thumbnail_id) : ?>
            <? use_component('wp-image', [
                'image_id' => $post->thumbnail_id,
                'class' => 'featured-image'
            ]); ?>
        <? endif; ?>
        
        <div class="text-content"><?= $post->content; ?></div>
    </div>
</template>
```

---

For more examples and advanced usage, check out the [WP-Easy Theme](https://github.com/drewbaker/wp-easy-theme/) starter theme.