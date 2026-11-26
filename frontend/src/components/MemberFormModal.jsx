import { useState } from "react";
import FormField from "./FormField";
import Modal from "./Modal";
import api, { getErrorMessage, getFieldErrors } from "../lib/api";

const EMPTY = { name: "", phone: "", email: "", birth_date: "", address: "" };

export default function MemberFormModal({ member, onClose, onSaved }) {
    const isEdit = Boolean(member);

    const [form, setForm] = useState(
        isEdit
            ? {
                  name: member.name ?? "",
                  phone: member.phone ?? "",
                  email: member.email ?? "",
                  birth_date: member.birth_date ?? "",
                  address: member.address ?? "",
              }
            : EMPTY,
    );
    const [submitting, setSubmitting] = useState(false);
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
            const res = isEdit
                ? await api.put(`/members/${member.id}`, form)
                : await api.post("/members", form);
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
            title={isEdit ? "Edit Member" : "Tambah Member"}
            onClose={onClose}>
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
                    label="No. HP"
                    name="phone"
                    value={form.phone}
                    onChange={onChange}
                    error={fieldErrors.phone}
                />
                <FormField
                    label="Email"
                    name="email"
                    type="email"
                    value={form.email}
                    onChange={onChange}
                    error={fieldErrors.email}
                />
                <FormField
                    label="Tanggal lahir"
                    name="birth_date"
                    type="date"
                    value={form.birth_date}
                    onChange={onChange}
                    error={fieldErrors.birth_date}
                />
                <FormField
                    label="Alamat"
                    name="address"
                    as="textarea"
                    rows={3}
                    value={form.address}
                    onChange={onChange}
                    error={fieldErrors.address}
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
        </Modal>
    );
}
