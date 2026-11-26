import { useEffect, useState } from "react";
import Modal from "./Modal";
import api, { getErrorMessage, getFieldErrors } from "../lib/api";
import { formatDate, formatRupiah } from "../lib/format";

export default function AddMembershipModal({
    member,
    activeUntil,
    onClose,
    onSaved,
}) {
    const [plans, setPlans] = useState(null);
    const [planId, setPlanId] = useState(null);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState("");
    const [fieldErrors, setFieldErrors] = useState({});

    useEffect(() => {
        api.get("/plans", { params: { active_only: 1 } })
            .then((res) => setPlans(res.data.data))
            .catch((err) => setError(getErrorMessage(err)));
    }, []);

    const onSubmit = async (e) => {
        e.preventDefault();
        if (!planId) return setError("Pilih plan terlebih dahulu.");

        setSubmitting(true);
        setError("");
        setFieldErrors({});

        try {
            const res = await api.post(`/members/${member.id}/memberships`, {
                membership_plan_id: planId,
            });
            onSaved(res.data.message);
        } catch (err) {
            setError(getErrorMessage(err));
            setFieldErrors(getFieldErrors(err));
            setSubmitting(false);
        }
    };

    return (
        <Modal title={`Membership untuk ${member.name}`} onClose={onClose}>
            <form onSubmit={onSubmit} className="space-y-4">
                {activeUntil && (
                    <div className="rounded-md bg-blue-50 px-3 py-2 text-sm text-blue-700">
                        Member masih aktif sampai {formatDate(activeUntil)}.
                        Periode baru otomatis dimulai setelah masa aktif itu
                        berakhir.
                    </div>
                )}

                {error && (
                    <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                        {error}
                    </div>
                )}

                {!plans && !error && (
                    <div className="text-sm text-gray-500">Memuat plan...</div>
                )}

                {plans && plans.length === 0 && (
                    <div className="text-sm text-gray-500">
                        Belum ada plan aktif.
                    </div>
                )}

                {plans && plans.length > 0 && (
                    <div className="space-y-2">
                        {plans.map((p) => (
                            <label
                                key={p.id}
                                className={`flex cursor-pointer items-center justify-between rounded-lg border px-4 py-3 ${
                                    planId === p.id
                                        ? "border-indigo-500 bg-indigo-50"
                                        : "border-gray-200 hover:bg-gray-50"
                                }`}>
                                <div className="flex items-center gap-3">
                                    <input
                                        type="radio"
                                        name="plan"
                                        checked={planId === p.id}
                                        onChange={() => setPlanId(p.id)}
                                    />
                                    <div>
                                        <div className="text-sm font-medium text-gray-900">
                                            {p.name}
                                        </div>
                                        <div className="text-xs text-gray-500">
                                            {p.duration_days} hari
                                        </div>
                                    </div>
                                </div>
                                <div className="text-sm font-semibold text-gray-900">
                                    {formatRupiah(p.price)}
                                </div>
                            </label>
                        ))}
                        {fieldErrors.membership_plan_id && (
                            <p className="text-xs text-red-600">
                                {fieldErrors.membership_plan_id[0]}
                            </p>
                        )}
                    </div>
                )}

                <div className="flex justify-end gap-2 pt-2">
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                        Batal
                    </button>
                    <button
                        type="submit"
                        disabled={submitting || !planId}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">
                        {submitting ? "Menyimpan..." : "Buat Membership"}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
