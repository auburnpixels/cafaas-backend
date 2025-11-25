<template>
    <div>
        <textarea
            :id="id"
            :name="name"
            :readonly="readonly"
            class="input"
            :rows="rows"
            :disabled="disabled"
            :class="{ 'input-error': errorMessage, 'input-readonly': readonly }"
            :maxlength="maxlength"
        >{{ value }}</textarea>

        <span v-if="errorMessage" class="mt-2 text-red-500 text-sm">{{ errorMessage }}</span>
    </div>
</template>

<script>
export default {

    props: {
        id: String,
        name: String,
        value: String,
        readonly: Boolean,
        errorMessage: String,
        rows: {
            default: 4,
            type: Number
        },
        maxlength: {
            default: 999999999,
            type: Number
        },
        disabled: {
            type: Boolean,
            default: false
        },
    },

}
</script>

<style>
textarea[disabled] {
    opacity: 0.5;
    background-color: rgb(221, 221, 221);
    cursor: not-allowed;
}
</style>
