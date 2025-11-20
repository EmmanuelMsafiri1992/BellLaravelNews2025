const mix = require('laravel-mix');

mix.js('resources/js/app.js', 'public/js')
    .sass('resources/sass/app.scss', 'public/css')
    .options({
        processCssUrls: false,
        postCss: [
            require('tailwindcss')('./tailwind.config.js'),
            require('autoprefixer'),
        ],
    });

// Process Tailwind CSS separately
mix.postCss('resources/css/input.css', 'public/css/tailwind.css', [
    require('tailwindcss')('./tailwind.config.js'),
    require('autoprefixer'),
]);

if (mix.inProduction()) {
    mix.version();
}
