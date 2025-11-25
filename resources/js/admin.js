require('./bootstrap');

import { createApp } from 'vue'

// Import page components
import ComplaintsIndex from './components/admin/pages/ComplaintsIndex.vue'
import ComplaintShow from './components/admin/pages/ComplaintShow.vue'
import ComplianceIndex from './components/admin/pages/ComplianceIndex.vue'
import DrawEventsIndex from './components/admin/pages/DrawEventsIndex.vue'
import RafflesIndex from './components/admin/pages/RafflesIndex.vue'
import RaffleShow from './components/admin/pages/RaffleShow.vue'

// Create Vue app
const app = createApp({})

// Register all admin page components globally
app.component('ComplaintsIndex', ComplaintsIndex)
app.component('ComplaintShow', ComplaintShow)
app.component('ComplianceIndex', ComplianceIndex)
app.component('DrawEventsIndex', DrawEventsIndex)
app.component('RafflesIndex', RafflesIndex)
app.component('RaffleShow', RaffleShow)

// Mount the app
app.mount('#admin-app')
