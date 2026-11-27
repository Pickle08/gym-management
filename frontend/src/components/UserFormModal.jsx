import { useState } from "react";
import FormField from "./FormField";
import Modal from "./Modal";
import api, { getErrorMessage, getFieldErrors } from "../lib/api";

export default function UserFormModal({ user, isSelf, onClose, onSaved }) {
    const isEdit = Boolean(user);

    const initial = {
        name: user?.name ?? "",
        email: user?.email ?? "",
        role: user?.role ?? "staff",
        is_active: user?.is_active ?? true,
    };

    const [form, setForm] = useState({
        ...initial,
        password: "",
        password_confirmation: "",
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

        let payload;
        if (isEdit) {
            // kirim hanya yang berubah, supaya sesi user nggak dicabut tanpa alasan
            payload = {};
            ["name", "email", "role", "is_active"].forEach((k) => {
                if (form[k] !== initial[k]) payload[k] = form[k];
            });
            if (form.password) {
                payload.password = form.password;
                payload.password_confirmation = form.password_confirmation;
            }
            if (Object.keys(payload).length === 0) return onClose();
        } else {
            payload = { ...form };
        }

        setSubmitting(true);
        setError("");
        setFieldErrors({});

        try {
            const res = isEdit
                ? await api.put(`/users/${user.id}`, payload)
                : await api.post("/users", payload);
            onSaved(res.data.message);
        } catch (err) {
            setError(getErrorMessage(err));
            setFieldErrors(getFieldErrors(err));
            setSubmitting(false);
        }
    };

    const hasFieldErrors = Object.keys(fieldErrors).length > 0;

    return (
        <Modal title={isEdit ? "Edit User" : "Tambah User"} onClose={onClose}>
            <form onSubmit={onSubmit} className="space-y-4">
                {error && !hasFieldErrors && (
                    <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                        {error}
                    </div>
                )}

                <FormField
                    label="Nama *"
                    name="name"
                    value={form.name}
                    onChange={onChange}
                    error={fieldErrors.name}
                />
                <FormField
                    label="Email *"
                    name="email"
                    type="email"
                    value={form.email}
                    onChange={onChange}
                    error={fieldErrors.email}
                />

                <FormField
                    label="Role *"
                    name="role"
                    as="select"
                    value={form.role}
                    onChange={onChange}
                    disabled={isSelf}
                    error={fieldErrors.role}>
                    <option value="admin">Admin</option>
                    <option value="staff">Staff</option>
                    <option value="trainer">Trainer</option>
                </FormField>

                <label className="flex items-center gap-2 text-sm text-gray-700">
                    <input
                        type="checkbox"
                        name="is_active"
                        checked={form.is_active}
                        onChange={onChange}
                        disabled={isSelf}
                    />
                    Akun aktif
                </label>

                {isSelf ? (
                    <p className="rounded-md bg-gray-50 px-3 py-2 text-xs text-gray-500">
                        Ini akun Anda. Role dan status nggak bisa diubah
                        sendiri. Untuk ganti password, pakai tombol “Ganti
                        password” di pojok kanan atas.
                    </p>
                ) : (
                    <>
                        <FormField
                            label={
                                isEdit
                                    ? "Password baru (opsional)"
                                    : "Password *"
                            }
                            name="password"
                            type="password"
                            autoComplete="new-password"
                            value={form.password}
                            onChange={onChange}
                            error={fieldErrors.password}
                        />
                        <FormField
                            label="Konfirmasi password"
                            name="password_confirmation"
                            type="password"
                            autoComplete="new-password"
                            value={form.password_confirmation}
                            onChange={onChange}
                            error={fieldErrors.password_confirmation}
                        />
                    </>
                )}

                {isEdit && !isSelf && (
                    <p className="rounded-md bg-amber-50 px-3 py-2 text-xs text-amber-700">
                        Mengubah role, password, atau menonaktifkan akun akan
                        mengeluarkan user dari semua sesi yang sedang aktif.
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
