import { useState } from "react";
import FormField from "./FormField";
import Modal from "./Modal";
import api, { getErrorMessage, getFieldErrors } from "../lib/api";

export default function PlanFormModal({ plan, onClose, onSaved }) {
    const isEdit = Boolean(plan);

    const [form, setForm] = useState({
        name: plan?.name ?? "",
        duration_days: plan?.duration_days ?? "",
        price: plan?.price ?? "",
        is_active: plan?.is_active ?? true,
    });
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState("");
    const [fieldErrors, setFieldErrors] = useState({});

    const onChange = (e) => {
        const { name, type, checked, value } = e.target;
        setForm({ ...form, [name]: type === "checkbox" ? checked : value });
    };

    const onSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setError("");
        setFieldErrors({});

        try {
            const payload = { ...form, name: form.name.trim() };
            const res = isEdit
                ? await api.put(`/plans/${plan.id}`, payload)
                : await api.post("/plans", payload);
            onSaved(res.data.message);
        } catch (err) {
            setError(getErrorMessage(err));
            setFieldErrors(getFieldErrors(err));
            setSubmitting(false);
        }
    };

    const hasFieldErrors = Object.keys(fieldErrors).length > 0;

    return (
        <Modal
            title={isEdit ? "Edit Plan" : "Tambah Plan"}
            onClose={onClose}
            maxWidth="max-w-md">
            <form onSubmit={onSubmit} className="space-y-4">
                {error && !hasFieldErrors && (
                    <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                        {error}
                    </div>
                )}

                <FormField
                    label="Nama plan *"
                    name="name"
                    value={form.name}
                    onChange={onChange}
                    error={fieldErrors.name}
                />
                <FormField
                    label="Durasi (hari) *"
                    name="duration_days"
                    type="number"
                    min={1}
                    value={form.duration_days}
                    onChange={onChange}
                    error={fieldErrors.duration_days}
                />
                <FormField
                    label="Harga (Rp) *"
                    name="price"
                    type="number"
                    min={0}
                    value={form.price}
                    onChange={onChange}
                    error={fieldErrors.price}
                />

                <label className="flex items-center gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        name="is_active"
                        checked={form.is_active}
                        onChange={onChange}
                    />
                    Aktif (bisa dipilih saat membuat membership)
                </label>

                {isEdit && (
                    <p className="rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-500">
                        Mengubah harga atau durasi tidak mengubah membership
                        yang sudah dibuat. Harganya disimpan sebagai snapshot
                        saat membership dibuat.
                    </p>
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
                        disabled={submitting}
                        className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">
                        {submitting ? "Menyimpan..." : "Simpan"}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
