# WP Easy - A framework for a modern WordPress theme, but make it easy. 

Build a modern WordPress theme, but without any of the hassle of modern frontend development. Easy to use, but respecting best practices.

## Features

- No build step
- Flexible reusable template routing
- Reusable component based approach
- SVG support built in
- SCSS support
- Font loading (local fonts, Adobe, Google and more)
- Opinionated strucutre, so you stick to best practices 

## Why

This theme framework is aimed at people who understand the basics of HTML/CSS/JS, but don't want to deal with the mess that modern frontend development has become. There is no build step. You don't need to now how to use terminal. Don't know what `npm` is? No problem, don't need it!

I built this theme framework so that my brother (an amazing graphic designer) could build themes purely through his FTP based code editor. He knows HTML and CSS really well, he doesn't know JS, but he does know jQuery. In my experince, this is common for people that have a job tangental to frontend web development - designers, copywriters, project managers and backend engineers.

My brother does want to learn, and he wants to build websites as close to the "right" way as possible. So I've attempted to create a framework that pushes him in a more modern direction. Component based. JS modules, SCSS (PostCSS not being possible server side just yet) and template routing.

## Documentation

### Router

The entry point for most people will be `/router.php`. This file determines what template is shown to the user. Templates are located in the theme's `/templates` directory.

You should setup your page and post's in the WordPress dashboard as you normally would. Enable [Pretty Permalinks](https://wordpress.org/documentation/article/customize-permalinks/#pretty-permalinks). Then in `/router.php` you can map out the templates you want to use depending on the URLs you want your site to support.

For example:

```
wp_easy_router([
    'home'          => ['path' => '/'],                                     // Will display the /template/home.php file
    'work'          => ['path' => '/work/'],                                // Will display the /template/work.php file
    'work-detail'   => ['path' => '/work/:spot/', 'template' => 'work'],    // Will display the /template/work.php file also
    'reel'          => ['path' => '/reel/'],                                // Will display the /template/reel.php file
    'article'       => ['path' => '/:article'],                             // Will display the /template/article.php file
]);
```

This syntax is similar to Node's Express path syntax. The key `home` is the route name, and the value is an array of `[path, template]`. The `path` is the URI you are trying to match to. The `template` is optional, and allows you to reuse the same template for multiple routes. If no template set, the key is used as the template name.

The router has a helper function `get_route_name()` that you can use to get the current active templates name.

### Templates & Components

Templates and components work very similary. The best way to think of theme is that you are building a website like a jigsaw puzzle. The components are each a peice of the puzzle. A template is where all the indervidual pieces (components) are put together.

- Templates are located in the theme's `/templates` directory.
- Components are located in the theme's `/component` directory.

A template or a component will always have `.php` file, that determines the HTML and strucutre. It will have an optional `.scss` or `.css` file for the styles that go with it, and an optional `.js` file for any specific JavaScript that is needed for that template or component. They are loaded automatically whenever you use a template or a component.

#### Templates

Your `/templates/work.php` might look something like this:

```
<?php use_component('header'); ?>

<main class="work">
    <?php use_component('work-block', ['title' => 'test of title argument']); ?>
</main>

<?php use_component('footer'); ?>
```

The framework will also load `/templates/work.scss` or `/templates/work.css`, and `/templates/work.js` if those files are present. These should be used for styles and JS that are specific and isolated only to this component. If you have global styles if JS that you need, the you can use the `/styles/main.scss` and `/js/main.js` files for these.

#### Components

Components are one of the central reasons this framework exists. They are very powerful once you get the hang of it.

In the above template example you'll see: 

```
<?php use_component('work-block', ['title' => 'Test of title argument']); ?>
``` 

What this is doing is similar to the WordPress function `get_template_part()`, it's loading in a reusable chunk of HTML (and PHP) code.

For exmaple, the `/components/work-block.php` component might look like this:

```
<?php
// Set default args for the component
$args = set_defaults($args, [
    'title' => 'Title default here',
]);
?>

<div class="work-block">
    <h2 class="title">
        <?php echo $args['title']; ?>
    </h2>
</div>
```

It's storngly recommend that you take the approch of keeping your components as isolated as possible. Meaning, that they only know data that is passed into them. So doing something like this is correct:

```
<?php use_component('work-block', ['title' => get_the_title()]); ?>
```

Each component can have it's own `.scss` or `.css` file, and a `.js` file. They are loaded automatically once, whenever the component is used. 

For example, `/components/work-block.scss` file might look like this:

```
@import '../styles/media-queries';

// Highly recommended that you namespace each component's CSS like this. Match the filename to the class!
.work-block {
    background-color: red;

    .title {
        color: blue;
    }

    // Media queries can be used like this, this will make the title black on phones
    @media #{$lt-phone} {
        background-color: yellow;
        
        .title {
            color: var(--color-black); // This is how you use native CSS variables that are defined in /styles/varibles.scss
        }
    }   
}
```

So this is `SCSS`, it's an extended version of the `CSS` you've seen before. It's big advanatge is it allows for nesting, and varibles. Native CSS allows for varibles nativly too, those are better and are used in `/styles/varibles.scss`, but some advanced varibles like the media-query one used in the example can't be used with native CSS vars. 

Nesting is coming to native CSS too eventually, but for now the SCSS synatx is easier to use. You can read about how [nesting works here](https://sass-lang.com/guide/#nesting).

The `@media #{$lt-phone}` is a custom media query defined in the `/styles/media-queries.scss` file. There are a few more ones defined there that will come in real handy for styling a component for different screen sizes. Note the `@import '../styles/media-queries';` statment at the top of the file, that is important (NOTE: one day I'd like to remove that as a requirment but for now you need it).

### Global Styles & Scripts

TODO Document how these work

- Global CSS vars and media-queries

### SCSS

TODO Document how SCSS work

### SVGs

`<img>` that are really SVGs are converted to `<svg>` on page load automatically. Just add `data-svg` to any `<img>` tag and it will load the underlying SVG and replace the `<img>`. This allows you to style SVGs in CSS.

For example, this:

```
<img data-svg class="svg" src="<?php echo get_template_directory_uri(); ?>/images/logo.svg">
```

Will be turned into this:
```
<svg data-svg="replaced" class="svg" version="1.1" baseProfile="tiny" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="157.8px" height="20.6px" viewBox="-225.9 375.3 157.8 20.6" xml:space="preserve" >
    //... SVG contents in here
</svg>
```

All attributes will be carred over also, note how `class="svg"` is added to the SVG for example. Also note that `data-svg="replaced"` is added to the converted SVGs.

Sometimes you might need to re-initlize SVGs, this can be done in JS like so:

```
import {initSVGs} from "wp-easy/svgs"
initSVGs()
```

### JavaScript

TODO Document how these work

- Auto including of JS
- JS and the state
- jQuery
- How to use the included modules

### SCSS

TODO Document how fonts work

- Font loaded events

## Notes

TODO Document anything else left over

- Open graph tags
- Note things I turned off as out of scope
    - comments
    - emojis

## TODO & Roadmap
- Single file components (and templates). For example, make this work: https://github.com/drewbaker/wp-easy/blob/dev/templates/work-detail.php
- JS combine & minify
- SCSS minify and inline
    - Would be nice if we could auto-load `media-queries` and `variables` into all `.scss` files.
- Make it a plugin not a theme
- Make a theme settings panel to control emojis, SVG uploads, etc...

## TODO - Drew's list
- How to do better JS?
    - Intersection Obververs?
    - Infinate scroll/pagination...
    - Slideshows...
- Move components so that each is in thier own directory? With template, JS and CSS files grouped? Is this a good idea?
- Should we use this for page animations? https://swup.js.org/getting-started/example/

# TODO Default components left to build
- WpImage
- WpMenu
- Extend $post object to include post_thumbnail_id. See: https://wordpress.stackexchange.com/a/240051    
- Example loop page