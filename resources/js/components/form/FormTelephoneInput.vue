<template>
    <div class="custom-tel-input">
        <div class="custom-input">
            <div class="mt-1">
                <input
                    :type="type"
                    :required="required"
                    @input="onInput($event.target.value)"
                    class="custom-input__input input text-sm md:text-base user-profile-details-input user-profile-details-input-background user-profile-details-input-border"
                    :value="value.value"
                    :placeholder="$attrs.placeholder"
                    ref="input"
                    name="phone_number"
                />
            </div>

            <div class="mt-2" v-if="value.value && value.value.length > 0">
                <span class="text-green-500" v-if="isValidNumber">Valid phone number</span>
                <span class="text-red-500" v-else>Invalid phone number</span>
            </div>
        </div>
    </div>
</template>

<script>
import { computed, defineComponent, onMounted, ref, reactive } from "vue";
import "intl-tel-input/build/css/intlTelInput.css";
import intlTelInput from "intl-tel-input";

export default defineComponent({
    name: "vue3-tel-input",

    setup(props, { emit }) {
        const input = ref();
        const telInput = ref();
        const isValidNumber = ref(false);
        const value = reactive({ value: null });

        onMounted(() => {
            const inputElement = input.value;
            telInput.value = intlTelInput(inputElement, {
                utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.12/js/utils.min.js",
                preferredCountries: ["gb", "us"],
            });
        });

        const hasError = computed(() => {
            return props.isDirty && !isValidNumber.value;
        });

        function onInput() {
            const formattedNumber = telInput.value.getNumber();
            isValidNumber.value = telInput.value.isValidNumber();

            this.value.value = formattedNumber
        }

        return {
            value,
            input,
            onInput,
            telInput,
            isValidNumber,
            hasError,
        };
    },

    props: {
        type: {
            default: "text",
            type: String,
        },
        required: {
            default: false,
            type: Boolean,
        },
        hasError: {
            type: Boolean,
            default: false,
        },
        errorMessage: {
            type: String,
        },
        isDirty: {
            default: false,
            type: Boolean,
        },
    }
});
</script>

<style>
.iti {
    width: 100%;
}
.iti__flag {
    background-image: url("https://intl-tel-input.com/intl-tel-input/img/flags.png");
}

@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    .iti__flag {
        background-image: url("https://intl-tel-input.com/intl-tel-input/img/flags@2x.png?1");
    }
}
</style>
