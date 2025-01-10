<button type="{{ $type }}">
    <slot :if="$slots['icon']" #icon />

    <slot>
        Default Button
    </slot>
</button>