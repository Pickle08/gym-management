import { useState } from "react";
import FormField from "./FormField";
import Modal from "./Modal";
import api, { getErrorMessage, getFieldErrors } from "../lib/api";

export default function ChangePasswordModal({ onClose }) {
    const [form, setForm] = useState({
        current_password: "",
        password: "",
        password_confirmation: "",
    });
    const [submitting, setSubmitting] = useState(false);
    const [done, setDone] = useState(false);
    const [error, setError] = useState("");
    const [fieldErrors, setFieldErrors] = useState({});

    const onChange = (e) =>
        setForm({ ...form, [e.target.name]: e.target.value });

    const onSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setError("");
        setFieldErrors({});

        try {
            await api.put("/auth/password", form);
            setDone(true);
        } catch (err) {
            setError(getErrorMessage(err));
            setFieldErrors(getFieldErrors(err));
        } finally {
            setSubmitting(false);
        }
    };

    const hasFieldErrors = Object.keys(fieldErrors).length > 0;

    return (
        <Modal title="Ganti Password" onClose={onClose} maxWidth="max-w-sm">
            {done ? (
                <div className="space-y-4">
                    <div className="rounded-md bg-green-50 px-3 py-2 text-sm text-green-700">
                        Password berhasil diganti. Perangkat lain akan keluar
                        otomatis.
                    </div>
                    <div className="flex justify-end">
                        <button
                            onClick={onClose}
                            className="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                            Tutup
                        </button>
                    </div>
                </div>
            ) : (
                <form onSubmit={onSubmit} className="space-y-4">
                    {error && !hasFieldErrors && (
                        <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                            {error}
                        </div>
                    )}

                    <FormField
                        label="Password saat ini"
                        name="current_password"
                        type="password"
                        autoComplete="current-password"
                        value={form.current_password}
                        onChange={onChange}
                        error={fieldErrors.current_password}
                    />
                    <FormField
                        label="Password baru (min. 8 karakter)"
                        name="password"
                        type="password"
                        autoComplete="new-password"
                        value={form.password}
                        onChange={onChange}
                        error={fieldErrors.password}
                    />
                    <FormField
                        label="Konfirmasi password baru"
                        name="password_confirmation"
                        type="password"
                        autoComplete="new-password"
                        value={form.password_confirmation}
                        onChange={onChange}
                        error={fieldErrors.password_confirmation}
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
                            {submitting ? "Menyimpan..." : "Simpan"}
                        </button>
                    </div>
                </form>
            )}
        </Modal>
    );
}
