import { ref, watch, onMounted } from 'vue'

// Create a singleton state outside the composable
const isDark = ref(false)
let isInitialized = false

export function useDarkMode() {
    // Apply dark mode class to HTML element
    const applyDarkMode = () => {
        if (isDark.value) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    }

    // Initialize dark mode from localStorage (only once)
    const initializeDarkMode = () => {
        if (isInitialized) return
        
        const stored = localStorage.getItem('darkMode')
        if (stored !== null) {
            isDark.value = stored === 'true'
        } else {
            isDark.value = false
        }
        applyDarkMode()
        isInitialized = true
    }

    // Toggle dark mode
    const toggle = () => {
        isDark.value = !isDark.value
        localStorage.setItem('darkMode', isDark.value.toString())
        applyDarkMode()
    }

    // Initialize on first mount only
    onMounted(() => {
        initializeDarkMode()
    })

    return {
        isDark,
        toggle
    }
}

