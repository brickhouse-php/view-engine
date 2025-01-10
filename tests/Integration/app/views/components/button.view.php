<button type="{{ $type }}">
    <slot v-if="$slots['icon']" #icon />

    <slot>
        Default Button
    </slot>
</button>