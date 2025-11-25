<template>
    <div>
        <label v-if="label" class="label mb-2">{{ label }}</label>

        <Datepicker
            :name="name"
            :autoApply="true"
            :readonly="readonly"
            :minutes-increment="15"
            v-model="datepickerValue"
            format="dd/MM/yyyy HH:mm"
            :closeOnAutoApply="false"
            :minDate="minimumDate"
            :maxDate="maximumDate"
            :class="{ 'input-readonly': readonly, 'input-error': errorMessage }"
            :startTime="startTime"
            :clearable="clearable"
            :hideInputIcon="true"
            :disabled="disabled"
        />

        <span v-if="errorMessage" class="block mt-2 text-red-500 text-sm">{{ errorMessage }}</span>
    </div>
</template>

<script>
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

export default {

    props: {
        name: String,
        label: String,
        value: String,
        readonly: Boolean,
        errorMessage: String,
        mode: {
            type: String,
            default: 'competition'
        },
        clearable: {
            type: Boolean,
            default: false,
        },
        disabled: {
            type: Boolean,
            default: false,
        },
        maximumDate: {
            default: null,
        },
    },

    data: function () {
        return {
            datepickerValue: this.value ? this.value.replace(/-/g, "/") : ''
        }
    },

    computed: {
        maxTime: function() {
          if (this.maximumDate) {
            console.log(this.maximumDate)
          }

          return null
        },
        minimumDate: function() {
            if (this.mode === 'competition') return this.roundToNearest15()
            return null
        },
        startTime: function () {
            return { hours: this.roundToNearest15().getHours(), minutes: this.roundToNearest15().getMinutes() }
        }
    },

    components: {
        Datepicker,
    },

    methods: {
        handleDate(modelData) {
            this.datepickerValue = new Date(modelData.replace(/-/g, "/"))
        },

        roundToNearest15(date = new Date()) {
            const minutes = 15
            const ms = 1000 * 60 * minutes

            return new Date(Math.round(date.getTime() / ms) * ms);
        }
    }
}
</script>

<style>
.dp__theme_light {
    --dp-background-color: #ffffff;
    --dp-text-color: #000000;
    --dp-hover-color: #f3f3f3;
    --dp-hover-text-color: #000000;
    --dp-hover-icon-color: #959595;
    --dp-primary-color: #000000;
    --dp-primary-text-color: #f8f5f5;
    --dp-secondary-color: #c0c4cc;
    --dp-border-color: #ddd;
    --dp-menu-border-color: #ddd;
    --dp-border-color-hover: #aaaeb7;
    --dp-disabled-color: #f6f6f6;
    --dp-scroll-bar-background: #f3f3f3;
    --dp-scroll-bar-color: #959595;
    --dp-success-color: #76d275;
    --dp-success-color-disabled: #a3d9b1;
    --dp-icon-color: #959595;
    --dp-danger-color: #ff6f60;
}
</style>
