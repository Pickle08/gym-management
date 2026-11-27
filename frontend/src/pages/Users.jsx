import { useEffect, useState } from "react";
import ConfirmModal from "../components/ConfirmModal";
import Notice from "../components/Notice";
import Pagination from "../components/Pagination";
import UserFormModal from "../components/UserFormModal";
import { useAuth } from "../context/AuthContext";
import useApiData from "../hooks/useApiData";
import useDebounce from "../hooks/useDebounce";
import api, { getErrorMessage } from "../lib/api";

const PER_PAGE = 10;

const ROLE_STYLE = {
    admin: "bg-purple-50 text-purple-700",
    staff: "bg-blue-50 text-blue-700",
    trainer: "bg-amber-50 text-amber-700",
};

export default function Users() {
    const { user: me } = useAuth();

    const [q, setQ] = useState("");
    const [role, setRole] = useState("");
    const [page, setPage] = useState(1);
    const debouncedQ = useDebounce(q, 400);

    const users = useApiData("/users", {
        q: debouncedQ || undefined,
        role: role || undefined,
        page,
        per_page: PER_PAGE,
    });

    const [notice, setNotice] = useState(null);
    const [form, setForm] = useState({ open: false, user: null });
    const [deactivateTarget, setDeactivateTarget] = useState(null);
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        setPage(1);
    }, [debouncedQ]);

    const items = users.data?.items ?? [];
    const meta = users.data?.meta ?? null;

    const onSaved = (message) => {
        setForm({ open: false, user: null });
        setNotice({ type: "success", text: message });
        users.reload();
    };

    const setActive = async (u, active) => {
        setBusy(true);
        try {
            const res = await api.put(`/users/${u.id}`, { is_active: active });
            setNotice({ type: "success", text: res.data.message });
            users.reload();
        } catch (err) {
            setNotice({ type: "error", text: getErrorMessage(err) });
        } finally {
            setBusy(false);
            setDeactivateTarget(null);
        }
    };

    const hasFilter = Boolean(debouncedQ || role);
    const linkBtn = "text-sm font-medium text-indigo-600 hover:underline";

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h1 className="text-xl font-semibold text-gray-900">Users</h1>
                <button
                    onClick={() => setForm({ open: true, user: null })}
                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    + Tambah User
                </button>
            </div>

            <Notice notice={notice} onClose={() => setNotice(null)} />

            <div className="flex flex-col gap-2 sm:flex-row">
                <input
                    type="search"
                    placeholder="Cari nama atau email..."
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:max-w-xs"
                />
                <select
                    value={role}
                    onChange={(e) => {
                        setRole(e.target.value);
                        setPage(1);
                    }}
                    className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">Semua role</option>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                    <option value="trainer">Trainer</option>
                </select>
            </div>

            {users.error && (
                <div className="flex items-center justify-between rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    <span>{users.error}</span>
                    <button
                        onClick={users.reload}
                        className="font-medium underline">
                        Coba lagi
                    </button>
                </div>
            )}

            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">Role</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            className={`divide-y divide-gray-100 ${users.loading ? "opacity-50" : ""}`}>
                            {items.map((u) => {
                                const isSelf = u.id === me.id;

                                return (
                                    <tr key={u.id}>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-gray-900">
                                                {u.name}
                                                {isSelf && (
                                                    <span className="ml-2 text-xs font-normal text-gray-400">
                                                        (Anda)
                                                    </span>
                                                )}
                                            </div>
                                            <div className="text-xs text-gray-500">
                                                {u.email}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className={`rounded-full px-2 py-0.5 text-xs font-medium uppercase ${
                                                    ROLE_STYLE[u.role] ??
                                                    "bg-gray-100 text-gray-600"
                                                }`}>
                                                {u.role}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3">
                                            {u.is_active ? (
                                                <span className="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                                    Aktif
                                                </span>
                                            ) : (
                                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                                    Nonaktif
                                                </span>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-3 text-right">
                                            <div className="flex justify-end gap-3">
                                                <button
                                                    className={linkBtn}
                                                    onClick={() =>
                                                        setForm({
                                                            open: true,
                                                            user: u,
                                                        })
                                                    }>
                                                    Edit
                                                </button>
                                                {!isSelf &&
                                                    (u.is_active ? (
                                                        <button
                                                            className="text-sm font-medium text-red-600 hover:underline"
                                                            onClick={() =>
                                                                setDeactivateTarget(
                                                                    u,
                                                                )
                                                            }>
                                                            Nonaktifkan
                                                        </button>
                                                    ) : (
                                                        <button
                                                            className={linkBtn}
                                                            disabled={busy}
                                                            onClick={() =>
                                                                setActive(
                                                                    u,
                                                                    true,
                                                                )
                                                            }>
                                                            Aktifkan
                                                        </button>
                                                    ))}
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>
                </div>

                {users.loading && !users.data && (
                    <div className="px-4 py-10 text-center text-sm text-gray-500">
                        Memuat...
                    </div>
                )}
                {!users.loading && !users.error && items.length === 0 && (
                    <div className="px-4 py-10 text-center text-sm text-gray-500">
                        {hasFilter
                            ? "Tidak ada user yang cocok."
                            : "Belum ada user."}
                    </div>
                )}

                <Pagination meta={meta} onPage={setPage} />
            </div>

            <p className="text-xs text-gray-500">
                User tidak bisa dihapus karena tercatat di riwayat pembayaran
                dan check-in. Nonaktifkan saja.
            </p>

            {form.open && (
                <UserFormModal
                    user={form.user}
                    isSelf={form.user?.id === me.id}
                    onClose={() => setForm({ open: false, user: null })}
                    onSaved={onSaved}
                />
            )}

            {deactivateTarget && (
                <ConfirmModal
                    title="Nonaktifkan user?"
                    confirmLabel="Nonaktifkan"
                    danger
                    busy={busy}
                    onConfirm={() => setActive(deactivateTarget, false)}
                    onClose={() => setDeactivateTarget(null)}>
                    <span className="font-medium text-gray-900">
                        {deactivateTarget.name}
                    </span>{" "}
                    nggak bisa login lagi dan akan langsung keluar dari semua
                    sesi.
                </ConfirmModal>
            )}
        </div>
    );
}
