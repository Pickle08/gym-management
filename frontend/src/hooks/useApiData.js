import { useEffect, useState } from "react";
import api, { getErrorMessage } from "../lib/api";

export default function useApiData(path, params, enabled = true) {
    const [state, setState] = useState({
        data: null,
        loading: enabled,
        error: "",
    });
    const [reloadKey, setReloadKey] = useState(0);
    const key = JSON.stringify(params); // key stabil, object baru tiap render nggak memicu fetch ulang

    useEffect(() => {
        if (!enabled) return;

        const controller = new AbortController();
        setState((s) => ({ ...s, loading: true, error: "" }));

        api.get(path, { params: JSON.parse(key), signal: controller.signal })
            .then((res) =>
                setState({ data: res.data.data, loading: false, error: "" }),
            )
            .catch((err) => {
                if (err.code === "ERR_CANCELED") return;
                setState({
                    data: null,
                    loading: false,
                    error: getErrorMessage(err),
                });
            });

        return () => controller.abort();
    }, [path, key, enabled, reloadKey]);

    return { ...state, reload: () => setReloadKey((k) => k + 1) };
}
