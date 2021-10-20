# spreadable/template

A composition-oriented template engine, written in PHP.

## Install

`composer install spreadable/template`


## The markers

There is 2 types of markers:
* `{user.name}`: a marker for **required** data
* `{?user.name}`: a marker for **optional** data


## Special meaning of a unique optional marker on an attribute

An attribute contains a unique optional marker and if the provided
value is `null`, on the rendering, that attribute is simply removed,
useful for boolean attributes.

## Basic templates

```php
use function Spreadable\Template\fragment;

/*
<p class="{?class}">{salutations} {user}</p>
*/
$paragraph = fragment('./paragraph.html');

/*
<em>{name}</em>
*/
$user = fragment('./user.html');

$visitor_paragraph = $paragraph([
    'salutations' => 'Hello',
    'user' => 'visitor'
]);

$jane_paragraph = $visitor_paragraph([
    'class' => 'woman',
    'user' => $user([
        'name' => 'Jane'
    ])
]);

$john_paragraph = $visitor_paragraph([
    'class' => 'man',
    'user' => $user([
        'name' => 'John'
    ])
]);

var_dump([
    'visitor' => $visitor_paragraph->serialize(), // <p>Hello visitor</p>
    'jane' => $jane_paragraph->serialize(), // <p class="woman">Hello <em>Jane</em></p>"
    'john' => $john_paragraph->serialize() // <p class="man">Hello <em>John</em></p>
]);
```

## Build an entire page

```php
// Additionally, to the previous code
use function Spreadable\Template\page;

/*
<link rel="stylesheet" href="/style.css?t={timestamp}" />
<script src="/main.js?t={timestamp}" type="module"></script>
*/
$head_fragment = fragment('./head-fragment.html');

/*
<header><h1>Branding Name</h1></header>
<nav></nav>
<main>
  <h1>Page title</h1>
  {contents}
</main>
*/ 
$body_fragment = fragment('./body-fragment.html');

$page = page(
    'en',
    'Branding Name',
    $head_fragment([
        'timestamp' => time()
    ]),
    $body_fragment([
        'contents' => [
            $visitor_paragraph,
            $jane_paragraph,
            $john_paragraph
        ]
    ])
);

echo $page->serialize();
/*
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Page title - Branding Name</title>
    <meta charset="utf-8">
    <link rel="stylesheet" href="/style.css?t=1634472822">
    <script src="/main.js?t=1634472822" type="module"></script>
  </head>
  <body>
    <header><h1>Branding Name</h1></header>
    <nav></nav>
    <main>
      <h1>Page title</h1>
      <p>Hello visitor</p>
      <p class="woman">Hello <em>Jane</em></p>
      <p class="man">Hello <em>John</em></p>
    </main>
  </body>
</html>
*/
```

## Prefilling

*Added in **1.1.0***

Sometimes, it can be interesting to prefill some templates to avoid making the entire filling job during the runtime.

For that reason, you can call `->serialize(string $prefix = null)` with a **prefix** matching the first marker segment.

You can now save them as files to be able to reuse them to only fill the rest during the runtime.

## License

[MIT](./license)
