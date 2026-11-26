import { useCallback, useEffect, useRef, useState } from "react";
import QrScanner from "../components/QrScanner";
import useDebounce from "../hooks/useDebounce";
import api, { getErrorMessage, getFieldErrors } from "../lib/api";
import { formatDate, formatTime } from "../lib/format";

const MODES = [
    { key: "code", label: "Scanner / Kode" },
    { key: "search", label: "Cari Member" },
    { key: "camera", label: "Kamera" },
];

function CodeInput({ onSubmit, busy }) {
    const [value, setValue] = useState("");
    const ref = useRef(null);

    // balikin fokus tiap selesai proses, supaya scanner USB bisa langsung dipakai lagi
    useEffect(() => {
        if (!busy) ref.current?.focus();
    }, [busy]);

    const submit = (e) => {
        e.preventDefault();
        const token = value.trim();
        if (!token) return;
        onSubmit({ qr_token: token });
        setValue("");
    };

    return (
        <form onSubmit={submit} className="space-y-2">
            <input
                ref={ref}
                autoFocus
                value={value}
                onChange={(e) => setValue(e.target.value)}
                placeholder="Scan QR atau tempel kode, lalu Enter"
                autoComplete="off"
                className="w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />
            <button
                type="submit"
                disabled={busy}
                className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">
                {busy ? "Memproses..." : "Check-in"}
            </button>
        </form>
    );
}

function MemberSearch({ onCheckIn, busy }) {
    const [q, setQ] = useState("");
    const debounced = useDebounce(q, 400);
    const [state, setState] = useState({
        items: [],
        loading: false,
        error: "",
    });

    useEffect(() => {
        if (debounced.trim().length < 2) {
            setState({ items: [], loading: false, error: "" });
            return;
        }

        const controller = new AbortController();
        setState((s) => ({ ...s, loading: true, error: "" }));

        api.get("/members", {
            params: { q: debounced.trim(), per_page: 5 },
            signal: controller.signal,
        })
            .then((res) =>
                setState({
                    items: res.data.data.items,
                    loading: false,
                    error: "",
                }),
            )
            .catch((err) => {
                if (err.code === "ERR_CANCELED") return;
                setState({
                    items: [],
                    loading: false,
                    error: getErrorMessage(err),
                });
            });

        return () => controller.abort();
    }, [debounced]);

    const searched = debounced.trim().length >= 2;

    return (
        <div className="space-y-3">
            <input
                type="search"
                value={q}
                onChange={(e) => setQ(e.target.value)}
                placeholder="Nama, kode, atau no. HP (min. 2 huruf)"
                className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
            />

            {state.error && (
                <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    {state.error}
                </div>
            )}

            <ul className="divide-y divide-gray-100 rounded-lg border border-gray-200">
                {state.items.map((m) => (
                    <li
                        key={m.id}
                        className="flex items-center justify-between gap-3 px-3 py-2">
                        <div className="min-w-0">
                            <div className="truncate text-sm font-medium text-gray-900">
                                {m.name}
                            </div>
                            <div className="text-xs text-gray-500">
                                {m.member_code} ·{" "}
                                {m.current_membership
                                    ? `Aktif s/d ${formatDate(m.current_membership.end_date)}`
                                    : "Tidak aktif"}
                            </div>
                        </div>
                        <button
                            onClick={() => onCheckIn({ member_id: m.id })}
                            disabled={busy}
                            className="shrink-0 rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">
                            Check-in
                        </button>
                    </li>
                ))}

                {searched &&
                    !state.loading &&
                    state.items.length === 0 &&
                    !state.error && (
                        <li className="px-3 py-6 text-center text-sm text-gray-500">
                            Member tidak ditemukan.
                        </li>
                    )}
                {state.loading && (
                    <li className="px-3 py-6 text-center text-sm text-gray-500">
                        Mencari...
                    </li>
                )}
            </ul>
        </div>
    );
}

export default function CheckIn() {
    const [mode, setMode] = useState("code");
    const [result, setResult] = useState(null);
    const [busy, setBusy] = useState(false);
    const busyRef = useRef(false);
    const lastScan = useRef({ token: "", at: 0 });

    const [today, setToday] = useState({
        items: [],
        total: 0,
        loading: true,
        error: "",
    });
    const [reloadKey, setReloadKey] = useState(0);

    useEffect(() => {
        const controller = new AbortController();

        api.get("/attendances", {
            params: { per_page: 50 },
            signal: controller.signal,
        })
            .then((res) =>
                setToday({
                    items: res.data.data.items,
                    total: res.data.data.meta.total,
                    loading: false,
                    error: "",
                }),
            )
            .catch((err) => {
                if (err.code === "ERR_CANCELED") return;
                setToday((s) => ({
                    ...s,
                    loading: false,
                    error: getErrorMessage(err),
                }));
            });

        return () => controller.abort();
    }, [reloadKey]);

    const checkIn = useCallback(async (payload) => {
        if (busyRef.current) return;
        busyRef.current = true;
        setBusy(true);

        try {
            const res = await api.post("/attendances/check-in", payload);
            setResult({ type: "success", data: res.data.data });
            setReloadKey((k) => k + 1);
        } catch (err) {
            const firstFieldError = Object.values(getFieldErrors(err))[0]?.[0];
            setResult({
                type: "error",
                message: firstFieldError || getErrorMessage(err),
            });
        } finally {
            busyRef.current = false;
            setBusy(false);
        }
    }, []);

    // Kamera memanggil callback ini terus-menerus selama QR masih di depan lensa.
    // Abaikan token yang sama dalam 8 detik supaya nggak jadi spam request dan
    // kartu hijau "berhasil" nggak ketimpa pesan "sudah check-in".
    const onScan = useCallback(
        (token) => {
            if (busyRef.current) return;
            const now = Date.now();
            if (
                token === lastScan.current.token &&
                now - lastScan.current.at < 8000
            )
                return;
            lastScan.current = { token, at: now };
            checkIn({ qr_token: token });
        },
        [checkIn],
    );

    return (
        <div className="space-y-4">
            <h1 className="text-xl font-semibold text-gray-900">Check-in</h1>

            <div className="grid gap-4 lg:grid-cols-2">
                <div className="space-y-4">
                    <div className="rounded-xl border border-gray-200 bg-white p-4">
                        <div className="mb-4 flex gap-1 rounded-lg bg-gray-100 p-1">
                            {MODES.map((m) => (
                                <button
                                    key={m.key}
                                    onClick={() => setMode(m.key)}
                                    className={`flex-1 rounded-md px-3 py-1.5 text-sm font-medium ${
                                        mode === m.key
                                            ? "bg-white text-indigo-700 shadow-sm"
                                            : "text-gray-600 hover:text-gray-900"
                                    }`}>
                                    {m.label}
                                </button>
                            ))}
                        </div>

                        {mode === "code" && (
                            <CodeInput onSubmit={checkIn} busy={busy} />
                        )}
                        {mode === "search" && (
                            <MemberSearch onCheckIn={checkIn} busy={busy} />
                        )}
                        {mode === "camera" && <QrScanner onScan={onScan} />}
                    </div>

                    <div aria-live="polite">
                        {result?.type === "success" && (
                            <div className="rounded-xl border border-green-200 bg-green-50 p-4">
                                <div className="text-sm font-medium text-green-700">
                                    Check-in berhasil
                                </div>
                                <div className="mt-1 text-xl font-semibold text-gray-900">
                                    {result.data.member.name}
                                </div>
                                <div className="text-sm text-gray-600">
                                    {result.data.member.member_code} ·
                                    Membership s/d{" "}
                                    {formatDate(
                                        result.data.membership_end_date,
                                    )}
                                </div>
                                <div className="text-xs text-gray-500">
                                    Pukul{" "}
                                    {formatTime(result.data.checked_in_at)}
                                </div>
                            </div>
                        )}

                        {result?.type === "error" && (
                            <div className="rounded-xl border border-red-200 bg-red-50 p-4">
                                <div className="text-sm font-medium text-red-700">
                                    Check-in ditolak
                                </div>
                                <div className="mt-1 text-base text-gray-900">
                                    {result.message}
                                </div>
                            </div>
                        )}
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div className="flex items-center justify-between border-b border-gray-200 px-4 py-3">
                        <h2 className="text-base font-semibold text-gray-900">
                            Check-in hari ini
                        </h2>
                        <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                            {today.total}
                        </span>
                    </div>

                    {today.error && (
                        <div className="px-4 py-3 text-sm text-red-700">
                            {today.error}
                        </div>
                    )}

                    {!today.error && today.items.length === 0 && (
                        <div className="px-4 py-10 text-center text-sm text-gray-500">
                            {today.loading
                                ? "Memuat..."
                                : "Belum ada check-in hari ini."}
                        </div>
                    )}

                    {today.items.length > 0 && (
                        <div className="max-h-[28rem] overflow-y-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="sticky top-0 bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th className="px-4 py-2">Jam</th>
                                        <th className="px-4 py-2">Member</th>
                                        <th className="px-4 py-2">Petugas</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {today.items.map((a) => (
                                        <tr key={a.id}>
                                            <td className="whitespace-nowrap px-4 py-2 font-mono text-xs text-gray-600">
                                                {formatTime(a.checked_in_at)}
                                            </td>
                                            <td className="px-4 py-2">
                                                <div className="font-medium text-gray-900">
                                                    {a.member?.name}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    {a.member?.member_code}
                                                </div>
                                            </td>
                                            <td className="whitespace-nowrap px-4 py-2 text-xs text-gray-600">
                                                {a.checked_in_by}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}
