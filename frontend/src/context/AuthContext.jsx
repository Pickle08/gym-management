import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
} from "react";
import api, { setUnauthorizedHandler, tokenStore } from "../lib/api";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [user, setUser] = useState(null);
    const [loading, setLoading] = useState(Boolean(tokenStore.get()));

    const clear = useCallback(() => {
        tokenStore.clear();
        setUser(null);
    }, []);

    useEffect(() => {
        setUnauthorizedHandler(clear);
    }, [clear]);

    // restore sesi saat halaman di-refresh
    useEffect(() => {
        if (!tokenStore.get()) return;

        api.get("/auth/me")
            .then((res) => setUser(res.data.data))
            .catch(() => clear())
            .finally(() => setLoading(false));
    }, [clear]);

    const login = useCallback(async (email, password) => {
        const res = await api.post("/auth/login", { email, password });
        tokenStore.set(res.data.data.token);
        setUser(res.data.data.user);
    }, []);

    const logout = useCallback(async () => {
        try {
            await api.post("/auth/logout");
        } catch {
            // token mungkin sudah invalid, tetap bersihkan sisi client
        }
        clear();
    }, [clear]);

    const value = useMemo(
        () => ({ user, loading, login, logout }),
        [user, loading, login, logout],
    );

    return (
        <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
    );
}

export function useAuth() {
    return useContext(AuthContext);
}
