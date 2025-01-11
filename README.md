# Brickhouse View Engine

This library is a templating engine for PHP applications. It has taken inspiration from [Blade](https://laravel.com/docs/11.x/blade), [Vue](https://vuejs.org) and [Tempest](https://github.com/tempestphp/tempest-framework).

## Installation

To install the library, you need to require the package via composer:

```bash
composer require brickhouse/view-engine
```

## Usage

To compile and render views, you need to create a resolver for views:

```php
use Brickhouse\View\Engine\Engine;

$resolver = new ViewResolver(basePath: __DIR__);
$renderer = new Renderer($resolver);
```

The `basePath` parameter to `ViewResolver` determines where the resolver will look for layouts, components and views. Depending on the type being resolved, the resolver will look in these directories:

| Type       | Path (relative to `basePath`) |
|------------|-------------------------------|
| Views      | `views/`                      |
| Layouts    | `views/layouts/`              |
| Components | `views/components/`           |

The renderer can then be used to compile and render templates into HTML:

```php
$rendered = $renderer->render('<p>Hello, Max.</p>');

// or...

$rendered = $renderer->renderFile('/path/to/view');
```

### Displaying data

You can use the renderer to interpolate content in the template. You can do so by wrapping the variable in curly braces:

```php
$template = '<p>Hello, {{ $name }}.</p>';
$rendered = $renderer->render($template, ['name' => 'Max']);
```

The template will then render as:

```html
<p>Hello, Max.</p>
```

You can also display the results of any PHP function - or really any PHP code:

```php
$template = '<p>UNIX Time: {{ time() }}</p>';
$rendered = $renderer->render($template);
```

### Conditions

The templating engine allows for conditions, which act on any data passed to the template. They provide a more concise way of adding conditional content to your template, without using PHP's control structures.

Conditional statements, along with other control structures, as defined using attributes:

```html
<p :if="count($rows) === 1">There is a single row.</p>
<p :else-if="count($rows) > 1">There is multiple rows.</p>
<p :else>There are no rows.</p>
```

Just like interpolation, these attributes support any PHP code which can be used inside a normal `if`-statement.

### Loops

In addition to conditional attributes, you can also iterate over elements in a list:

```html
<p :for="$i = 0; $i < 10; $i++">Row index: {{ $i }}</p>

<p :foreach="$users as $user">User ID: {{ $user->id }}</p>
```

### Components

Components allow the template to re-use the same markup multiple places in your application.

You can create a component by creating a new file in the `views/components` directory, with a `.view.php` extension, such as `alert.view.php`:

```html
<div type="alert">
    An error occured.
</div>
```

You can then reference the component using it's relative path, prefixed with `x-`:

```html
<html>
    <body>
        <x-alert></x-alert>
    </body>
</html>
```

#### Passing attributes

A static component can only do so much, so components also support passing attributes to them, which can then be used inside of the component. For example, if we change the component to use the template:

```html
<div type="alert" class="alert alert-{{ $type }}">
    {{ $message }}
</div>
```

Then, we can pass the message using an attribute, when using it:

```html
<html>
    <body>
        <x-alert type="error" message="User could not be found."></x-alert>
    </body>
</html>
```

#### Slots

You will often want to create more complex components, which needs more than just text interpolation. For this, you can use "slots", which will replace parts of the component markup. Let's take the same component as before, but use slots for it's content:

```html
<div type="alert">
    <slot />
</div>
```

Then, when we reference the component, we can pass markup into it's body content:

```html
<x-alert>
    <b>Error:</b> user could not be found.
</x-alert>
```

This will emplace the given content into the `slot`-tag and render into the following:

```html
<div type="alert">
    <b>Error:</b> user could not be found.
</div>
```

#### Fallback / default content

You also don't need to define the content of the slot every time, if it will likely stay the same. For that, it might be better to define the default content of a slot, like so:

```html
<div type="alert">
    <slot>
        <!-- Default content -->
        An error occured.
    </slot>
</div>
```

If the component is referenced without providing the slot, `An error occured.` will be shown:

```html
<x-alert></x-alert>
```

This will render the default content:

```html
<div type="alert">
    An error occured.
</div>
```

But if we provide content for the slot:

```html
<x-alert>
    <b>Warning:</b> connection lost.
</x-alert>
```

Then the provided content will be rendered instead:

```html
<div type="alert">
    <b>Warning:</b> connection lost.
</div>
```

#### Named slots

Sometimes, you need more than a single slot for a component. To facilitate that, you can use "named slots". Let's use the same `alert` component:

```html
<div type="alert">
    <span class="alert-title">
        <slot #title />
    </span>

    <slot />
</div>
```

We have defined an additional slot named `title`. Slots are named by prefixing the name with a hashtag (`#`) as an attribute. We can then define the content of the given slot, by using `template`-tags:

```html
<x-alert>
    </template #title>
        Could not save user.
    </template>

    <template>
        <b>Error:</b> user could not be found.
    </template>
</x-alert>
```

Here, the `template`-tag with the `#title` attribute defines the content of the `title`-slot, whereas the `template`-tag without any attributes defines the content of the default slot.

### Layouts

Views in an application often share a lot of common elements, such as navigation, headers, footers, etc. To define the common interface, you can use layouts. Layouts are defined much like components, but are stored within `views/layouts/` instead of `views/components/`:

```html
<!-- /views/layouts/app.view.php -->

<!DOCTYPE html>
<html>
    <head>
        <title>Application Title</title>
    </head>
    <body>
        <slot />
    </body>
</html>
```

Within the layout, we define a `slot`-tag, which defines where the view content is meant to be rendered. To use the layout, you can reference it by prefixing `x-layout::` to the layout path:

```html
<!-- /views/dashboard.view.php -->

<x-layout::app>
    <h1>Dashboard</h1>
</x-layout::app>
```

### Passing attributes

Much like components, layouts also support passing attributes:

```html
<!DOCTYPE html>
<html>
    <head>
        <title>{{ $title ?? 'Application Title' }}</title>
    </head>
    <body>
        <slot />
    </body>
</html>
```