# WP Easy - A framework for a modern WordPress theme, but make it easy. 

Build a modern WordPress theme, but without any of the hassle of modern frontend development. Easy to use, but respecting best practices.

## Features

- No build step
- Flexible reusable template routing
- Reusable component based approach
- Single File Components
- SVG support built in
- SCSS support
- Font loading (local fonts, Adobe, Google and more)
- Opinionated strucutre, so you stick to best practices 
- Auto loading of 3rd party JS libraries

## Why

This theme framework is aimed at people who understand the basics of HTML/CSS/JS, but don't want to deal with the mess that modern frontend development has become. There is no build step. You don't need to now how to use terminal. Don't know what `npm` is? No problem, don't need it!

I built this theme framework so that my brother (an amazing graphic designer) could build themes purely through his FTP based code editor. He knows HTML and CSS really well, he doesn't know JS, but he does know jQuery. In my experince, this is common for people that have a job tangental to frontend web development - designers, copywriters, project managers and backend engineers.

My brother does want to learn, and he wants to build websites as close to the "right" way as possible. So I've attempted to create a framework that pushes him in a more modern direction. Component based. JS modules, SCSS (PostCSS not being possible server side just yet) and template routing.

## Documentation

### Router

The entry point for most people will be the themes `/router.php` file. This file determines what template is shown to the user, and whay layout file to place that template in. 

Layouts are located in the theme's `/layouts` directory.
Templates are located in the theme's `/templates` directory.

You should setup your page and post's in the WordPress dashboard as you normally would. Enable [Pretty Permalinks](https://wordpress.org/documentation/article/customize-permalinks/#pretty-permalinks). Then in `/router.php` you can map out the templates you want to use depending on the URLs you want your site to support.

For example:

```
$routes = [
    'home'              => '/',
    'work'              => '/work/',
    // Would use the /layouts/alternate.php layout, with the /templates/work.php page template
    'work-detail'       => ['path' => '/work/:spot/', 'layout' => 'alternate', 'template' => 'work'],
    'reel'              => '/reel/',
];

return $routes;
```

This syntax is similar to Node's Express path syntax. The key `home` is the route name, and the value is an array of `[path, template, layout]`. The `path` is the URI you are trying to match to. Note you can use a short string syntax instead of the array syntax of `name => '/path/'` for simple routes. 

The `layout` is optional, and allows you to set a different layout file to wrap the template. If no layout set, the `/layouts/default.php` file is used.

The `template` is optional, and allows you to reuse the same template for multiple routes. If no template set, the key is used as the template name.

The router has a helper function `get_route_name()` that you can use to get the current active templates name.

### Single File Components

THe big inovation of WP Easy is the concept of Single File Components (SFC). These SFC's have been inspired by Vue's SFC, but now they work in WordPress.

Page templates and components can use SFC syntax, layouts can't. And exmaple of a empty SFC is:

```
<?php // Some PHP here ?>
<template>
    <div class="example">Some HTML in here</div>
</template>

<style>
    .example {}
</style>

<script>
    $('.example').click(function(){
        // Some JS on click of this element 
    })
</script>
```

### Layouts

Layouts are the top level strucutre of the page. You will generally always have a `/layout/default.php` layout. This would contain global elements like headers or footers. It must contain a few required functions, so see the WP Easy starter theme for reference.

Layouts are not Single File Components. So put your styles in main.scss and scripts in main.js. 

- Layouts are located in the theme's `/layouts` directory.

###  Templates & Components

Templates and components work very similary. The best way to think of theme is that you are building a website like a jigsaw puzzle. The components are each a peice of the puzzle. A template is where all the indervidual pieces (components) are put together.

- Templates are located in the theme's `/templates` directory.
- Components are located in the theme's `/component` directory.

A template, or a component will always be a `.php` file, that determines the HTML, Styles and JS. 

#### Templates

Your `/templates/work.php` might look something like this:

```
<template>
<?php use_component('header'); ?>

<main class="work">
    <?php use_component('work-block', ['title' => 'test of title argument']); ?>
</main>

<?php use_component('footer'); ?>
</template>

<style>
@import 'media-queries';

.work {
    background: red;

    .work-block {
        .background: blue; // YES, this is SCSS and works out of the box!
    }

    // Media queries can be used like this, this will make the title black on phones
    @media #{$lt-phone} {
        background-color: yellow;
        
        .work-block {
            background: var(--color-black); // This is how you use native CSS variables that are defined in /styles/varibles.scss
        }
    }   
}
</style>

<script>
$('.work').click(()=>{
    console.log('Something clicked!')
})
</script>
```

The `style` block is actually `SCSS`, it's an extended version of the `CSS` you've seen before. It's big advanatge is it allows for nesting, and varibles. Native CSS allows for varibles nativly too, those are better and are used in `/styles/varibles.scss`, but some advanced varibles like the media-query one used in the example can't be used with native CSS vars. 

Nesting is coming to native CSS too eventually, but for now the SCSS synatx is easier to use. You can read about how [nesting works here](https://sass-lang.com/guide/#nesting).

The `@media #{$lt-phone}` is a custom media query defined in the `/global/media-queries.scss` file. There are a few more ones defined there that will come in real handy for styling a component for different screen sizes. Note the `@import 'media-queries';` statment at the top of the file, that is important (NOTE: one day I'd like to remove that as a requirment but for now you need it).

If you have global styles or JS that you need, the you can use the `/styles/main.scss` and `/scripts/main.js` files for these.

#### Components

Components are one of the central reasons this framework exists. They are very powerful once you get the hang of it.

In your template, you can call `use_component($name, $props = [])`. For exmaple:

```
<?php use_component('work-block', ['title' => 'Test of title argument']); ?>
``` 

What this is doing is similar to the WordPress function `get_template_part()`, it's loading in a reusable chunk of HTML (and PHP) code.

For exmaple, the `/components/work-block.php` component might look like this:

```
<?php
// Set default props for the component
$args = set_defaults($args, [
    'title' => 'Title default here',
]);
?>

<template>
    <div class="work-block">
        <h2 class="title">
            <?= $args['title']; ?>
        </h2>
    </div>
</template>

<style>
.work-block {
    background: red;
    .title {
        color: blue;
    }
}
</style>
```

It's storngly recommend that you take the approch of keeping your components as isolated as possible. Meaning, that they only know data that is passed into them via a prop. So doing something like this is correct:

```
<?php use_component('work-block', ['title' => get_the_title()]); ?>
```

Each component can have it's own `<style>` or `<script>` block in it, just like templates. They are loaded automatically once, whenever the component is used. 

### Global Styles & Scripts

In all templates and components (but not layouts) you have access to Single File Component style block, and the abibility ti `@import` some useful media queries. Like so:

```
<style>
@import 'media-queries'; // This is needed!

.example {
    background: red;

    // Media queries can be used like this
    @media #{$lt-phone} {
        background: green;
    }   
}
</style>
```

### SCSS

All templates and components can use a Single File Component style block that supports [SCSS syntax](https://devhints.io/sass). The big advantage to SCSS is the abibility to nest selectors.

NOTE: It is strongly recommend that you namespace all CSS under one class, and that name matchs the file name of your component or template.

```
<style>
@import 'media-queries';

// It is strongly recommend that you namespace all CSS under one class, and that name matchs the file name of your component or template.
.example {
    background: red;

    .title {
        .color: blue; // YES, this is SCSS and works out of the box!
    }

    @media #{$lt-phone} {
        background-color: yellow;
        
        .title {
            color: var(--color-black); // This is a CSS var that is declared in /styles/varibles.scss
        }
    }   
}
</style>
```

### SVGs

`use_svg('logo', ['class' => 'foo'])` will render the SVG found in theme `/images/logo.svg` and give it an attribute of `class="foo"`. 

Note: Any prop you pass will be added a HTML attribute on the SVG.

So the above, will be turned into this:
```
<svg class="foo" version="1.1" baseProfile="tiny" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="157.8px" height="20.6px" viewBox="-225.9 375.3 157.8 20.6" xml:space="preserve" >
    //... SVG contents in here
</svg>
```

### JavaScript

TODO Better document how JS works

- Auto including of JS, anything in `/scripts/libs/` will be auto enqueued as a module
- jQuery `$` works globally
- How to use the included modules

### Fonts

TODO Better document how fonts work

- Create a folder `/fonts`
- Add fonts to `/styles/fonts.css`
- Add font name to Webfontloader in `/scripts/fonts.js`
- Font loaded events

### Helper functions

TODO Better documentation of these

- `use_component($name, $props = [])`
- `use_children($WpQueryArgs = [])`
- `use_svg($name, $attrs = [])`
- `use_outlet()`
- `get_route_name()`
- `set_defaults($props = [])` for setting defaults on component props


## TODO - Features
- Use default layout when using fallback page template
- JS blocks as files, as modules, combine & minify.
- SCSS minify and inline
    - Would be nice if we could auto-load anything in `/styles/global/` into all `.scss` files so no need to use `@import`.
- Make a theme settings panel to control disable emojis, SVG uploads, etc...
- Auto load any new CSS files? What order? Maybe 

## TODO - DX improvments 
- How to do better JS?
    - Intersection Obververs?
    - Infinate scroll/pagination...
    - Slideshows...
- Should we use this for page animations? https://swup.js.org/getting-started/example/
- Add Favicon to dashboard, see `wp_site_icon()` and `get_site_icon_url()`
- Live reload when in dev mode? https://github.com/ryantate13/php-live-reload
- Bring accross Focal Point picker and default ACF groups
- Document anything else left over
    - Document open graph tags
    - Document things I turned off as out of scope
        - comments
        - emojis
