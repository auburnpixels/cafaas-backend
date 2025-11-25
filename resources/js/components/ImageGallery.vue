<template>
    <div>
        <div class="aspect-w-3 aspect-h-4 rounded-xl overflow-hidden lg:block h-full">
            <video
                v-if="activeImage?.type && activeImage.type === 'video'"
                controls
                :src="activeImage ? baseUrl + activeImage?.location : ''"
                class="w-full h-full object-center object-cover max-h-[48rem] detail-page-card-background"
            ></video>
            <a v-else :href="activeImage ? baseUrl + activeImage?.location : null" target="_blank">
                <img
                    :src="activeImage ? baseUrl + activeImage?.location : ''"
                    :alt="activeImage?.alt"
                    loading="lazy"
                    class="w-full h-full object-center object-cover max-h-[48rem] detail-page-card-background"
                />
            </a>
        </div>

        <div v-if="images && showThumbnails" class="mt-5">
            <swiper-container slides-per-view="3" space-between="10" :navigation="true" speed="500">
                <swiper-slide v-for="image in images">
                    <div v-if="image?.type && image.type === 'video'" class="relative">
                        <video
                            @click="setActiveImage(image)"
                            :src="image ? baseUrl + image?.location : ''"
                            :class="{'border-[2px] border-raffaly-primary': image.location === activeImage.location}"
                            class="image-gallery-preview cursor-pointer object-cover rounded-xl h-[100px] md:h-[190px] w-full detail-page-card-background"
                        ></video>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="absolute w-10 h-10 text-white top-[42%] left-[38%]"><path d="M12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12C22 17.5228 17.5228 22 12 22ZM12 20C16.4183 20 20 16.4183 20 12C20 7.58172 16.4183 4 12 4C7.58172 4 4 7.58172 4 12C4 16.4183 7.58172 20 12 20ZM10.6219 8.41459L15.5008 11.6672C15.6846 11.7897 15.7343 12.0381 15.6117 12.2219C15.5824 12.2658 15.5447 12.3035 15.5008 12.3328L10.6219 15.5854C10.4381 15.708 10.1897 15.6583 10.0672 15.4745C10.0234 15.4088 10 15.3316 10 15.2526V8.74741C10 8.52649 10.1791 8.34741 10.4 8.34741C10.479 8.34741 10.5562 8.37078 10.6219 8.41459Z" fill="currentColor"></path></svg>
                    </div>
                    <img
                        v-else
                        loading="lazy"
                        :alt="image?.alt"
                        :src="baseUrl + image.location"
                        @click="setActiveImage(image)"
                        :class="{'border-[2px] border-raffaly-primary': image.location === activeImage.location}"
                        class="image-gallery-preview cursor-pointer object-cover rounded-xl h-[100px] md:h-[190px] w-full detail-page-card-background"
                    />
                </swiper-slide>
            </swiper-container>
        </div>
    </div>
</template>

<script>
import { register } from 'swiper/element/bundle';

export default {
    components: {
    },

    props: {
        promotionalImage: {
            type: String,
            default: null,
        },
        promotionalVideo: {
            type: String,
            default: null,
        },
        images: {
            type: Array,
            default: []
        },
        baseUrl: String,
        showThumbnails: {
            type: Boolean,
            default: true,
        }
    },

    data() {
        return {
            activeImage: this.images[0],
        }
    },

    mounted() {
        register()
    },

    methods: {
        setActiveImage(image) {
            this.activeImage = image
        }
    }

}
</script>
