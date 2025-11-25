<template>
    <Icon :name="name" :class-name="className"></Icon>
</template>

<script>

export default {
    props: {
        name: String,
        active: {
            type: Boolean,
            default: false
        }
    },

    computed: {

        className() {
            let defaultClasses = "flex-shrink-0 -ml-1 mr-3 h-6 w-6 mr-2 "

            if (this.active) defaultClasses += 'user-profile-menu-active'
            if (!this.active) defaultClasses += 'user-profile-menu-inactive'

            return defaultClasses
        }

    }
}
</script>
