<template>
    <div class="mb-5 lg:mb-10 mt-5 flex flex-col gap-2">
        <div class="page-header-border flex flex-col lg:flex-row justify-between lg:items-center">
            <div class="flex flex-col">
                <h1 class="text-2xl lg:text-3xl tracking-tight font-extrabold md:text-4xl" v-if="title">{{ title }}</h1>
                <p class="page-header-subtext mt-2 lg:mt-2 text-base md:text-lg" v-if="description" v-html="description"></p>
            </div>
            <slot name="actions"></slot>
        </div>
        <slot name="description" class="mb-10"></slot>
    </div>
</template>

<script>
export default {
    props: {
        title: String,
        description: String
    }
}
</script>
