import { useEffect, useState } from "react";
import MemberFormModal from "../components/MemberFormModal";
import Modal from "../components/Modal";
import Pagination from "../components/Pagination";
import QrModal from "../components/QrModal";
import { useAuth } from "../context/AuthContext";
import useDebounce from "../hooks/useDebounce";
import api, { getErrorMessage } from "../lib/api";
import { formatDate } from "../lib/format";
import { Link } from "react-router-dom";

const PER_PAGE = 10;

export default function Members() {
    const { user } = useAuth();
    const canWrite = user.role === "admin" || user.role === "staff";
    const isAdmin = user.role === "admin";

    const [q, setQ] = useState("");
    const [membership, setMembership] = useState("");
    const [page, setPage] = useState(1);
    const debouncedQ = useDebounce(q, 400);

    const [list, setList] = useState({
        items: [],
        meta: null,
        loading: true,
        error: "",
    });
    const [reloadKey, setReloadKey] = useState(0);
    const [notice, setNotice] = useState(null);

    const [formState, setFormState] = useState({ open: false, member: null });
    const [qrTarget, setQrTarget] = useState(null);
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [deleting, setDeleting] = useState(false);

    // pencarian baru selalu mulai dari halaman 1
    useEffect(() => {
        setPage(1);
    }, [debouncedQ]);

    useEffect(() => {
        const controller = new AbortController();
        setList((s) => ({ ...s, loading: true, error: "" }));

        api.get("/members", {
            params: {
                q: debouncedQ || undefined,
                membership: membership || undefined,
                page,
                per_page: PER_PAGE,
            },
            signal: controller.signal,
        })
            .then((res) =>
                setList({
                    items: res.data.data.items,
                    meta: res.data.data.meta,
                    loading: false,
                    error: "",
                }),
            )
            .catch((err) => {
                if (err.code === "ERR_CANCELED") return; // request lama dibatalkan, abaikan
                setList((s) => ({
                    ...s,
                    loading: false,
                    error: getErrorMessage(err),
                }));
            });

        return () => controller.abort();
    }, [debouncedQ, membership, page, reloadKey]);

    const reload = () => setReloadKey((k) => k + 1);

    const onSaved = (message) => {
        setFormState({ open: false, member: null });
        setNotice({ type: "success", text: message });
        reload();
    };

    const confirmDelete = async () => {
        setDeleting(true);
        try {
            const res = await api.delete(`/members/${deleteTarget.id}`);
            setNotice({ type: "success", text: res.data.message });
            if (list.items.length === 1 && page > 1) setPage((p) => p - 1);
            else reload();
        } catch (err) {
            setNotice({ type: "error", text: getErrorMessage(err) });
        } finally {
            setDeleting(false);
            setDeleteTarget(null);
        }
    };

    const hasFilter = Boolean(debouncedQ || membership);
    const linkBtn = "text-sm font-medium text-indigo-600 hover:underline";

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h1 className="text-xl font-semibold text-gray-900">Members</h1>
                {canWrite && (
                    <button
                        onClick={() =>
                            setFormState({ open: true, member: null })
                        }
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        + Tambah Member
                    </button>
                )}
            </div>

            {notice && (
                <div
                    className={`flex items-start justify-between rounded-md px-3 py-2 text-sm ${
                        notice.type === "success"
                            ? "bg-green-50 text-green-700"
                            : "bg-red-50 text-red-700"
                    }`}>
                    <span>{notice.text}</span>
                    <button
                        onClick={() => setNotice(null)}
                        aria-label="Tutup"
                        className="ml-3">
                        ✕
                    </button>
                </div>
            )}

            <div className="flex flex-col gap-2 sm:flex-row">
                <input
                    type="search"
                    placeholder="Cari nama, kode, atau no. HP..."
                    value={q}
                    onChange={(e) => setQ(e.target.value)}
                    className="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500 sm:max-w-xs"
                />
                <select
                    value={membership}
                    onChange={(e) => {
                        setMembership(e.target.value);
                        setPage(1);
                    }}
                    className="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500">
                    <option value="">Semua status</option>
                    <option value="active">Membership aktif</option>
                    <option value="inactive">Tidak aktif</option>
                </select>
            </div>

            {list.error && (
                <div className="flex items-center justify-between rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    <span>{list.error}</span>
                    <button onClick={reload} className="font-medium underline">
                        Coba lagi
                    </button>
                </div>
            )}

            <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                        <thead className="bg-gray-50 text-xs uppercase text-gray-500">
                            <tr>
                                <th className="px-4 py-3">Kode</th>
                                <th className="px-4 py-3">Nama</th>
                                <th className="px-4 py-3">Membership</th>
                                <th className="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            className={`divide-y divide-gray-100 ${list.loading ? "opacity-50" : ""}`}>
                            {list.items.map((m) => (
                                <tr key={m.id}>
                                    <td className="whitespace-nowrap px-4 py-3 font-mono text-xs text-gray-600">
                                        {m.member_code}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Link
                                            to={`/members/${m.id}`}
                                            className="font-medium text-gray-900 hover:text-indigo-600 hover:underline">
                                            {m.name}
                                        </Link>
                                        <div className="text-xs text-gray-500">
                                            {m.phone || m.email || "-"}
                                        </div>
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3">
                                        {m.current_membership ? (
                                            <span className="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                                Aktif s/d{" "}
                                                {formatDate(
                                                    m.current_membership
                                                        .end_date,
                                                )}
                                            </span>
                                        ) : (
                                            <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                                Tidak aktif
                                            </span>
                                        )}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-right">
                                        <div className="flex justify-end gap-3">
                                            {canWrite && (
                                                <button
                                                    className={linkBtn}
                                                    onClick={() =>
                                                        setQrTarget(m)
                                                    }>
                                                    QR
                                                </button>
                                            )}
                                            {canWrite && (
                                                <button
                                                    className={linkBtn}
                                                    onClick={() =>
                                                        setFormState({
                                                            open: true,
                                                            member: m,
                                                        })
                                                    }>
                                                    Edit
                                                </button>
                                            )}
                                            {isAdmin && (
                                                <button
                                                    className="text-sm font-medium text-red-600 hover:underline"
                                                    onClick={() =>
                                                        setDeleteTarget(m)
                                                    }>
                                                    Hapus
                                                </button>
                                            )}
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {list.loading && list.items.length === 0 && (
                    <div className="px-4 py-10 text-center text-sm text-gray-500">
                        Memuat...
                    </div>
                )}

                {!list.loading && !list.error && list.items.length === 0 && (
                    <div className="px-4 py-10 text-center text-sm text-gray-500">
                        {hasFilter
                            ? "Tidak ada member yang cocok dengan pencarian."
                            : "Belum ada member."}
                    </div>
                )}

                <Pagination meta={list.meta} onPage={setPage} />
            </div>

            {formState.open && (
                <MemberFormModal
                    member={formState.member}
                    onClose={() => setFormState({ open: false, member: null })}
                    onSaved={onSaved}
                />
            )}

            {qrTarget && (
                <QrModal member={qrTarget} onClose={() => setQrTarget(null)} />
            )}

            {deleteTarget && (
                <Modal
                    title="Hapus member?"
                    onClose={() => setDeleteTarget(null)}
                    maxWidth="max-w-sm">
                    <p className="text-sm text-gray-600">
                        <span className="font-medium text-gray-900">
                            {deleteTarget.name}
                        </span>{" "}
                        ({deleteTarget.member_code}) akan dihapus. Riwayat
                        pembayaran dan kehadirannya tetap tersimpan.
                    </p>
                    <div className="mt-4 flex justify-end gap-2">
                        <button
                            onClick={() => setDeleteTarget(null)}
                            className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            Batal
                        </button>
                        <button
                            onClick={confirmDelete}
                            disabled={deleting}
                            className="rounded-md bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60">
                            {deleting ? "Menghapus..." : "Hapus"}
                        </button>
                    </div>
                </Modal>
            )}
        </div>
    );
}
