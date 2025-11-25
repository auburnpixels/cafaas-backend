<template>
    <div class="api-playground">
        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Select Endpoint</label>
            <select 
                v-model="selectedEndpoint" 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
                <option value="audit">GET /raffles/{uuid}/audit</option>
                <option value="entries">GET /raffles/{uuid}/entries/stats</option>
                <option value="odds">GET /raffles/{uuid}/odds</option>
            </select>
        </div>

        <div class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Raffle UUID</label>
            <input 
                v-model="raffleUuid" 
                type="text" 
                placeholder="550e8400-e29b-41d4-a716-446655440000"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <p class="text-xs text-gray-500 mt-1">Use sandbox UUIDs for testing (see documentation)</p>
        </div>

        <div v-if="selectedEndpoint === 'odds'" class="mb-6">
            <label class="block text-sm font-medium text-gray-700 mb-2">Number of Entries</label>
            <input 
                v-model.number="oddsEntries" 
                type="number" 
                min="1"
                placeholder="5"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
        </div>

        <div class="mb-6">
            <button 
                @click="sendRequest" 
                :disabled="loading"
                class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors font-medium"
            >
                {{ loading ? 'Sending Request...' : 'Send Request' }}
            </button>
        </div>

        <div v-if="requestUrl" class="mb-6">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Request URL</h3>
            <div class="bg-gray-100 p-4 rounded-lg font-mono text-sm break-all">
                {{ requestUrl }}
            </div>
        </div>

        <div v-if="response" class="mb-6">
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-semibold text-gray-700">Response</h3>
                <span 
                    :class="[
                        'px-3 py-1 rounded-full text-xs font-medium',
                        response.status >= 200 && response.status < 300 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                    ]"
                >
                    {{ response.status }} {{ response.statusText }}
                </span>
            </div>
            <div class="bg-gray-900 text-white p-4 rounded-lg overflow-auto" style="max-height: 500px;">
                <pre class="text-sm"><code>{{ formatJson(response.data) }}</code></pre>
            </div>
        </div>

        <div v-if="error" class="mb-6">
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-red-700">{{ error }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    name: 'ApiPlayground',
    
    data() {
        return {
            selectedEndpoint: 'audit',
            raffleUuid: '550e8400-e29b-41d4-a716-446655440000',
            oddsEntries: 9,
            loading: false,
            response: null,
            error: null,
            requestUrl: null,
        };
    },
    
    methods: {
        async sendRequest() {
            this.loading = true;
            this.error = null;
            this.response = null;
            
            if (!this.raffleUuid) {
                this.error = 'Please enter a raffle UUID';
                this.loading = false;
                return;
            }
            
            try {
                let url = `/api/v1/raffles/${this.raffleUuid}`;
                
                switch (this.selectedEndpoint) {
                    case 'audit':
                        url += '/audit';
                        break;
                    case 'entries':
                        url += '/entries/stats';
                        break;
                    case 'odds':
                        if (!this.oddsEntries || this.oddsEntries < 1) {
                            this.error = 'Please enter a valid number of entries (minimum 1)';
                            this.loading = false;
                            return;
                        }
                        url += `/odds?entries=${this.oddsEntries}`;
                        break;
                }
                
                this.requestUrl = window.location.origin + url;
                
                const response = await fetch(url, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-Request-Id': this.generateRequestId(),
                    },
                });
                
                const data = await response.json();
                
                this.response = {
                    status: response.status,
                    statusText: response.statusText,
                    data: data,
                    headers: Object.fromEntries(response.headers.entries()),
                };
            } catch (err) {
                this.error = `Request failed: ${err.message}`;
            } finally {
                this.loading = false;
            }
        },
        
        formatJson(data) {
            return JSON.stringify(data, null, 2);
        },
        
        generateRequestId() {
            return 'req_' + Math.random().toString(36).substring(2, 15);
        },
    },
};
</script>

<style scoped>
.api-playground {
    max-width: 100%;
}
</style>

