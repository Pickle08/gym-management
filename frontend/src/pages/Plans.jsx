import { useState } from "react";
import ConfirmModal from "../components/ConfirmModal";
import Notice from "../components/Notice";
import PlanFormModal from "../components/PlanFormModal";
import useApiData from "../hooks/useApiData";
import api, { getErrorMessage } from "../lib/api";
import { formatRupiah } from "../lib/format";

export default function Plans() {
    const plans = useApiData("/plans", {});
    const [notice, setNotice] = useState(null);
    const [form, setForm] = useState({ open: false, plan: null });
    const [deleteTarget, setDeleteTarget] = useState(null);
    const [busy, setBusy] = useState(false);
    const [togglingId, setTogglingId] = useState(null);

    const items = plans.data ?? [];

    const onSaved = (message) => {
        setForm({ open: false, plan: null });
        setNotice({ type: "success", text: message });
        plans.reload();
    };

    const toggleActive = async (plan) => {
        setTogglingId(plan.id);
        try {
            const res = await api.put(`/plans/${plan.id}`, {
                is_active: !plan.is_active,
            });
            setNotice({ type: "success", text: res.data.message });
            plans.reload();
        } catch (err) {
            setNotice({ type: "error", text: getErrorMessage(err) });
        } finally {
            setTogglingId(null);
        }
    };

    const confirmDelete = async () => {
        setBusy(true);
        try {
            const res = await api.delete(`/plans/${deleteTarget.id}`);
            setNotice({ type: "success", text: res.data.message });
            plans.reload();
        } catch (err) {
            setNotice({ type: "error", text: getErrorMessage(err) });
        } finally {
            setBusy(false);
            setDeleteTarget(null);
        }
    };

    const linkBtn =
        "text-sm font-medium text-indigo-600 hover:underline disabled:opacity-50";

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-2">
                <h1 className="text-xl font-semibold text-gray-900">
                    Membership Plans
                </h1>
                <button
                    onClick={() => setForm({ open: true, plan: null })}
                    className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                    + Tambah Plan
                </button>
            </div>

            <Notice notice={notice} onClose={() => setNotice(null)} />

            {plans.error && (
                <div className="flex items-center justify-between rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    <span>{plans.error}</span>
                    <button
                        onClick={plans.reload}
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
                                <th className="px-4 py-3">Durasi</th>
                                <th className="px-4 py-3">Harga</th>
                                <th className="px-4 py-3">Status</th>
                                <th className="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody
                            className={`divide-y divide-gray-100 ${plans.loading ? "opacity-50" : ""}`}>
                            {items.map((p) => (
                                <tr key={p.id}>
                                    <td className="px-4 py-3 font-medium text-gray-900">
                                        {p.name}
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-gray-600">
                                        {p.duration_days} hari
                                    </td>
                                    <td className="whitespace-nowrap px-4 py-3 text-gray-900">
                                        {formatRupiah(p.price)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {p.is_active ? (
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
                                                        plan: p,
                                                    })
                                                }>
                                                Edit
                                            </button>
                                            <button
                                                className={linkBtn}
                                                disabled={togglingId === p.id}
                                                onClick={() => toggleActive(p)}>
                                                {p.is_active
                                                    ? "Nonaktifkan"
                                                    : "Aktifkan"}
                                            </button>
                                            <button
                                                className="text-sm font-medium text-red-600 hover:underline"
                                                onClick={() =>
                                                    setDeleteTarget(p)
                                                }>
                                                Hapus
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {plans.loading && !plans.data && (
                    <div className="px-4 py-10 text-center text-sm text-gray-500">
                        Memuat...
                    </div>
                )}
                {!plans.loading && !plans.error && items.length === 0 && (
                    <div className="px-4 py-10 text-center text-sm text-gray-500">
                        Belum ada plan.
                    </div>
                )}
            </div>

            <p className="text-xs text-gray-500">
                Plan yang sudah pernah dipakai membership tidak bisa dihapus,
                cukup dinonaktifkan. Plan nonaktif tidak muncul di pilihan saat
                membuat membership baru.
            </p>

            {form.open && (
                <PlanFormModal
                    plan={form.plan}
                    onClose={() => setForm({ open: false, plan: null })}
                    onSaved={onSaved}
                />
            )}

            {deleteTarget && (
                <ConfirmModal
                    title="Hapus plan?"
                    confirmLabel="Hapus"
                    danger
                    busy={busy}
                    onConfirm={confirmDelete}
                    onClose={() => setDeleteTarget(null)}>
                    Plan{" "}
                    <span className="font-medium text-gray-900">
                        {deleteTarget.name}
                    </span>{" "}
                    akan dihapus permanen.
                </ConfirmModal>
            )}
        </div>
    );
}
