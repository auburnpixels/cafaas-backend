<template>
    <div>
        <div class="">
            <draggable
                item-key="order"
                handle=".handle"
                @end="drag = false"
                @start="drag = true"
                v-model="uploadedImages"
                class="border-b rounded-br rounded-bl"
            >
                <template #item="{element, index}">
                    <div
                        class="flex flex-col border border-b-0 py-[0.5rem] px-[0.75rem] input rounded-none text-sm md:text-base"
                        :class="{
                            'rounded-tr rounded-tl': (index === 0),
                        }"
                    >
                        <div class="flex justify-between items-center">
                            <button v-if="element.image && maxImages > 1" class="handle bg-rafferly-dark-gray h-6 w-6 rounded flex items-center justify-center mr-5">
                                <svg xmlns="http://www.w3.org/2000/svg" class="text-gray-400 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                </svg>
                            </button>
                            <div style="flex: 9;">
                                <div v-if="element.name">
                                    <text-link :href="assetCdnUrl + element.image" target="_blank">{{ element.name }}</text-link>
                                </div>

                                <div class="flex w-full justify-between items-center">
                                    <input
                                        v-if="!element.id"
                                        type="file"
                                        :accept="accept"
                                        @change="previewImage($event, index)"
                                        :id="name + '[' + getInputKey(index) + '][' + inputName + ']'"
                                        :name="name + '[' + getInputKey(index) + '][' + inputName + ']'"
                                        autocomplete="off"
                                        class="focus:border-transparent focus:outline-none w-11/12"
                                    />

                                    <input
                                        type="hidden"
                                        :value="element?.id"
                                        style="text-indent: -999999999px"
                                        :name="name + '[' + getInputKey(index) + '][id]'"
                                    />
                                </div>
                            </div>
                            <div class="flex flex-1 items-start justify-end ml-5">
                                <button v-if="element.image" @click.prevent="remove(element, index)" class="bg-rafferly-dark-gray h-6 w-6 rounded flex items-center justify-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </template>
            </draggable>
        </div>

        <span v-if="errorMessage" class="mt-2 text-red-500 text-sm block">{{ errorMessage }}</span>
    </div>
</template>

<script>

import _ from 'lodash'
import draggable from 'vuedraggable'

export default {
    components: {
        draggable
    },

    data: function () {
        return {
            drag: false,
            uploadedImages: this.value || []
        }
    },

    computed: {
        assetCdnUrl() {
            return document.querySelector('meta[name="cdn"]').content
        },
    },

    mounted() {
        if (
            (this.maxImages === -1) ||
            (this.maxImages > 1) ||
            ((this.maxImages == 1) && (this.uploadedImages.length === 0))
        ) {
            this.uploadedImages = this.uploadedImages.filter((uploadedImage) => uploadedImage.id !== null)

            this.uploadedImages.push({ alt: null, image: null })
        }
    },

    props: {
        name: {
            type: String,
            default: 'images'
        },
        value: Array,
        images: Array,
        maxImages: {
         type: Number,
         default: -1
        },
        withAlt: {
            type: Boolean,
            default: true
        },
        errorMessage: String,
        subDetailsTitle: {
            type: String,
            default: 'Alt text'
        },
        subDetailsDescription: {
            type: String,
            default: 'This will be some help text explaining alt text\n'
        },
        accept: {
            type: String,
            default: 'image/*'
        },
        inputName: {
            type: String,
            default: 'image'
        },
        useInputNameAsIndex: {
            type: String,
            default: false,
        }
    },

    methods: {
        remove(element, key) {
            const uploadedImages = _.cloneDeep(this.uploadedImages)

            uploadedImages.splice(key, 1);

            if (uploadedImages.length === 0) uploadedImages.push({ alt: null, image: null })

            this.uploadedImages = uploadedImages
        },

        getInputKey(index) {
            if (this.useInputNameAsIndex === 'true') return this.inputName
            return index
        },

        previewImage(event, index) {
            const self = this
            // Reference to the DOM input element
            const input = event.target;
            // Ensure that you have a file before attempting to read it
            if (input.files && input.files[0]) {
                // create a new FileReader to read this image and convert to base64 format
                const reader = new FileReader();
                // Define a callback function to run, when FileReader finishes its job
                reader.onload = (e) => {
                    // Note: arrow function used here, so that "this.imageData" refers to the imageData of Vue component
                    // Read image as base64 and set to imageData
                    self.uploadedImages[index].alt = ''
                    self.uploadedImages[index].image = e.target.result;

                    if ((this.maxImages === -1) || (this.maxImages > 1)) self.uploadedImages.push({ alt: null, image: null });
                }
                // Start the reader job - read file as a data url (base64 format)
                reader.readAsDataURL(input.files[0]);
            }
        }
    }

}
</script>
