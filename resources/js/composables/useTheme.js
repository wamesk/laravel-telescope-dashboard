import { ref } from 'vue';

const STORAGE_KEY = 'telescope-dashboard-theme';

const isDark = ref(true);

function applyTheme() {
    if (isDark.value) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
}

// Initialize from localStorage
const stored = localStorage.getItem(STORAGE_KEY);
if (stored !== null) {
    isDark.value = stored === 'dark';
} else {
    isDark.value = true;
}
applyTheme();

export function useTheme() {
    function toggleTheme() {
        isDark.value = !isDark.value;
        localStorage.setItem(STORAGE_KEY, isDark.value ? 'dark' : 'light');
        applyTheme();
    }

    return {
        isDark,
        toggleTheme,
    };
}
