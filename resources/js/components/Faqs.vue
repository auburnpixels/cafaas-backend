<template>
    <dl class="space-y-2">
        <Disclosure as="div" v-for="faq in faqs" :key="faq.question" v-slot="{ open }" class="bg-white p-5 rounded-xl faq">
            <dt class="text-lg bg-white rounded-xl">
                <DisclosureButton class="text-left w-full flex justify-between items-start">
                    <span class="text-base md:text-lg font-medium faq-page-question">
                      {{ faq.question }}
                    </span>
                    <span class="ml-6 h-7 flex items-center">
                        <ChevronDownIcon class="faq-page-icon" :class="[open ? '-rotate-180' : 'rotate-0', 'h-6 w-6 transform']" aria-hidden="true" />
                    </span>
                </DisclosureButton>
            </dt>
            <DisclosurePanel as="dd" class="mt-4 mb-4 pr-12">
                <p class="text-base md:text-lg faq-page-answer break-words text-gray-700" v-html="faq.answer"></p>
            </DisclosurePanel>
        </Disclosure>
    </dl>
</template>

<script>
import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/vue'
import { ChevronDownIcon } from '@heroicons/vue/outline'

export default {
    components: {
        Disclosure,
        DisclosureButton,
        DisclosurePanel,
        ChevronDownIcon,
    },

    props: {
        faqs: Object,
    },

}
</script>
