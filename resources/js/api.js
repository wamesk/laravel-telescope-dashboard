const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

const mountEl = document.getElementById('telescope-dashboard');
const config = mountEl ? JSON.parse(mountEl.dataset.config || '{}') : {};

const basePath = config.basePath || '/telescope-dashboard';

export function getConfig() {
    return config;
}

export function getTranslations() {
    const mountEl = document.getElementById('telescope-dashboard');
    return mountEl ? JSON.parse(mountEl.dataset.translations || '{}') : {};
}

export async function apiPost(endpoint, data = {}) {
    const url = `${basePath}/api${endpoint}`;

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: JSON.stringify(data),
    });

    if (!response.ok) {
        const error = await response.json().catch(() => ({ message: 'Request failed' }));
        throw new Error(error.message || `HTTP ${response.status}`);
    }

    return response.json();
}

export async function apiGet(endpoint) {
    const url = `${basePath}/api${endpoint}`;

    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken || '',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
    });

    if (!response.ok) {
        const error = await response.json().catch(() => ({ message: 'Request failed' }));
        throw new Error(error.message || `HTTP ${response.status}`);
    }

    return response.json();
}
