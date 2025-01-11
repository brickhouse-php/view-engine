<!DOCTYPE html>
<html>

<head>
    <title>{{ $title ?? 'Example Title' }}</title>
</head>

<body>
    <slot #navbar />

    <slot>
        Default Content
    </slot>
</body>

</html>