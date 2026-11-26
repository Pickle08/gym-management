import axios from "axios";

const TOKEN_KEY = "gym_token";

export const tokenStore = {
    get: () => localStorage.getItem(TOKEN_KEY),
    set: (token) => localStorage.setItem(TOKEN_KEY, token),
    clear: () => localStorage.removeItem(TOKEN_KEY),
};

const api = axios.create({
    baseURL: import.meta.env.VITE_API_URL,
    headers: { Accept: "application/json" },
});

api.interceptors.request.use((config) => {
    const token = tokenStore.get();
    if (token) config.headers.Authorization = `Bearer ${token}`;
    return config;
});

// AuthContext mendaftarkan handler ini supaya 401 di mana pun = logout otomatis
let onUnauthorized = () => {};
export const setUnauthorizedHandler = (fn) => {
    onUnauthorized = fn;
};

api.interceptors.response.use(
    (res) => res,
    (error) => {
        const isLogin = error.config?.url?.includes("/auth/login");
        if (error.response?.status === 401 && !isLogin) onUnauthorized();
        return Promise.reject(error);
    },
);

export function getErrorMessage(error) {
    if (!error.response) return "Tidak bisa terhubung ke server.";
    return error.response.data?.message || "Terjadi kesalahan.";
}

// 422 validasi: { field: ['pesan', ...] }. Selain itu: {}
export function getFieldErrors(error) {
    return error.response?.data?.errors || {};
}

export default api;
