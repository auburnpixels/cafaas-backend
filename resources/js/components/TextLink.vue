<template>
    <a :href="href" class="text-link inline-flex items-center">
        <slot></slot>
        <Icon v-if="externalLink" name="external-link" class-name="h-3 w-3 ml-1"></Icon>
    </a>
</template>

<script>
export default {

    props: {
        href: String,
        link: String,
        externalLink: {
            type: Boolean,
            default: false
        }
    }

}
</script>
