<template>
    <div>
        <vue-editor :editor-toolbar="toolbar" v-model="content" />
        <input type="hidden" :name="name" :value="content" />

        <span v-if="errorMessage" class="block mt-2 text-red-500 text-sm">{{ errorMessage }}</span>
    </div>
</template>

<script>
import { VueEditor } from "vue3-editor";

export default {

    props: {
        value: String,
        name: String,
        errorMessage: String,
        toolbar: {
            default: [
                ["bold", "italic", "underline"],
                [{ 'header': [2, 3, 4, 5, 6, false] }],
                [{ list: "ordered" }, { list: "bullet" }],
                ["link", "video"]
            ]
        }
    },

    components: {
        VueEditor
    },

    data() {
        return {
            content: this.value,
        }
    },
}
</script>
