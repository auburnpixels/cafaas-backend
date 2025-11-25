<template>
    <div>
        <label v-if="label" class="label mb-2">{{ label }}</label>

        <color-picker
            use-type="pure"
            shape="circle"
            :disable-alpha="true"
            v-model:pureColor="value"
            @pureColorChange="updatePureColor"
        />
        <input type="hidden" :name="name" :value="color" />

        <span v-if="errorMessage" class="mt-2 text-red-500 text-sm">{{ errorMessage }}</span>
    </div>

</template>

<script>
import { ColorPicker } from 'vue3-colorpicker'
import "vue3-colorpicker/style.css";

export default {

    props: {
        id: String,
        name: String,
        value: String,
        label: String,
        errorMessage: String,
        brandClass: {
            type: String,
            default: ''
        },
        required: {
            type: Boolean,
            default: false
        }
    },

    components: {
        ColorPicker
    },

    data() {
        return {
            color: this.value ?? null
        }
    },

    methods: {
        updatePureColor(color) {
            this.color = color
        }
    }

}
</script>

<style type="sa">

.vc-color-wrap {
    box-shadow: none !important;
    width: 30px !important;
    height: 30px !important;
    border-radius: 10px !important;
}

</style>
