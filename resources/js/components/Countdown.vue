<template>
    <span class="card-countdown whitespace-nowrap">{{ countdown }}</span>
</template>

<script>
import moment from 'moment'

export default {

    props: {
        date: String,
        timezone: String,
    },

    data() {
        return {

            countdown: null,
            timestamp: moment(this.date)
        }
    },

    mounted() {
      this.setCountdownTimer(this.timezone)
    },

    methods: {
        setCountdownTimer() {
            const self = this

            let dateInLocale = new Date().toLocaleString("en-US", { timeZone: this.timezone });
            let now = new Date(dateInLocale).getTime();
            const countDownDate = new Date(this.date.replace(/\s/, 'T')).getTime();

            const distance = countDownDate - now;

            // Time calculations for days, hours, minutes and seconds
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);

            self.countdown = days + "d " + hours + "h " + minutes + "m " + seconds + "s";

            // Update the count down every 1 second
            const x = setInterval(function() {
                let dateInLocale = new Date().toLocaleString("en-US", { timeZone: self.timezone });
                let now = new Date(dateInLocale).getTime();
                const distance = countDownDate - now;

                // Time calculations for days, hours, minutes and seconds
                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                // If the count down is finished, write some text
                if (distance < 0) {
                    clearInterval(x);
                } else {
                    self.countdown = days + "d " + hours + "h " + minutes + "m " + seconds + "s";
                }
            }, 1000);
        },
    }

}
</script>
