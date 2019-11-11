## Installation

```bash
composer require supercharge/supercharge
```

You can now set your Supercharge URL by setting the `SUPERCHARGE_URL` environment variable in your `.env` file:

```dotenv
SUPERCHARGE_URL=https://... # Set your Supercharge URL here
```

Remember that Supercharge is useful in production, not in development. You probably want to set `SUPERCHARGE_URL` in the `.env` file of your production server.

## Advanced configuration

You can configure all the options by publishing the package config file in your project:

```bash
php artisan vendor:publish --provider="Supercharge\\ServiceProvider"
```

This will create a `config/supercharge.php` that you can customize.

## Usage

Given you display images in your Blade templates:

```html
<img src="/img/cats.jpg">
{{-- or an image URL stored in a model: --}}
<img src="{{ $user->avatar }}">
```

Wrap the image URLs with the `supercharge()` helper:

```blade
<img src="{{ supercharge('/img/cats.jpg') }}">
<img src="{{ supercharge($user->avatar) }}">
```

That's it!

### Resize

You can easily display resized images using Supercharge. You don't have to change the original images, Supercharge will automatically generate resized and optimized images on the fly.

- Width:

```blade
<img src="{{ supercharge('/img/cats.jpg')->width(250) }}">
```

- Height:

```blade
<img src="{{ supercharge('/img/cats.jpg')->height(250) }}">
```

- Both parameters can be combined:

```blade
<img src="{{ supercharge('/img/cats.jpg')->width(250)->height(400) }}">
```

The way the image is resized to fit in the dimension can be customized via the `fit` option:

```blade
<img src="{{ supercharge('/img/cats.jpg')->width(250)->fit('crop') }}">
```

Possible values are:

- `contain` (default): Scale the image to fit within the target dimensions, without cropping or stretching.
- `max`: Same as `contain`, except the image will not be scaled up.
- `stretch`: Stretches the image to fit the target dimensions exactly.
- `crop`: Crop the image to fill the target dimensions.

### Device pixel ratio

To easily support retina displays (for example on mobile), you can set a device pixel ratio higher than 1 (the default). You must specify a size (width or height) with this parameter. The maximum value is 8.

```blade
<img src="{{ supercharge('/img/cats.jpg')->width(250)->pixelRatio(2) }}">
```

This will generate an image with a width of 500 pixels.

### Flip

You can flip the image on the horizontal and vertical axes:

```blade
<img src="{{ supercharge('/img/cats.jpg')->flipHorizontal() }}">
<img src="{{ supercharge('/img/cats.jpg')->flipVertical() }}">
<img src="{{ supercharge('/img/cats.jpg')->flipBoth() }}">
```
