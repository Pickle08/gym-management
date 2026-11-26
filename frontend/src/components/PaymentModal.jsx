import { useState } from "react";
import FormField from "./FormField";
import Modal from "./Modal";
import api, { getErrorMessage, getFieldErrors } from "../lib/api";
import { formatRupiah } from "../lib/format";

export default function PaymentModal({ membership, onClose, onSaved }) {
    const [method, setMethod] = useState("cash");
    const [reference, setReference] = useState("");
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState("");
    const [fieldErrors, setFieldErrors] = useState({});

    const onSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setError("");
        setFieldErrors({});

        try {
            const res = await api.post(
                `/memberships/${membership.id}/payments`,
                {
                    amount: membership.price, // backend mewajibkan nominal pas
                    method,
                    reference: reference || undefined,
                },
            );
            onSaved(res.data.message);
        } catch (err) {
            setError(getErrorMessage(err));
            setFieldErrors(getFieldErrors(err));
            setSubmitting(false);
        }
    };

    return (
        <Modal title="Catat Pembayaran" onClose={onClose} maxWidth="max-w-sm">
            <form onSubmit={onSubmit} className="space-y-4">
                <div className="rounded-lg bg-gray-50 px-4 py-3">
                    <div className="text-xs text-gray-500">
                        {membership.plan?.name}
                    </div>
                    <div className="text-2xl font-semibold text-gray-900">
                        {formatRupiah(membership.price)}
                    </div>
                    <div className="mt-1 text-xs text-gray-500">
                        Dibayar lunas, tanpa cicilan.
                    </div>
                </div>

                {error && (
                    <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                        {error}
                    </div>
                )}

                <FormField
                    label="Metode"
                    name="method"
                    as="select"
                    value={method}
                    onChange={(e) => setMethod(e.target.value)}
                    error={fieldErrors.method}>
                    <option value="cash">Tunai</option>
                    <option value="transfer">Transfer</option>
                    <option value="qris">QRIS</option>
                </FormField>

                <FormField
                    label="Referensi (opsional)"
                    name="reference"
                    placeholder="No. transaksi / catatan"
                    value={reference}
                    onChange={(e) => setReference(e.target.value)}
                    error={fieldErrors.reference}
                />

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
                        {submitting ? "Memproses..." : "Konfirmasi Pembayaran"}
                    </button>
                </div>
            </form>
        </Modal>
    );
}
