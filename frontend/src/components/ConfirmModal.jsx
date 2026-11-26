import Modal from "./Modal";

export default function ConfirmModal({
    title,
    children,
    confirmLabel = "Ya",
    danger = false,
    busy = false,
    onConfirm,
    onClose,
}) {
    return (
        <Modal title={title} onClose={onClose} maxWidth="max-w-sm">
            <div className="text-sm text-gray-600">{children}</div>
            <div className="mt-4 flex justify-end gap-2">
                <button
                    onClick={onClose}
                    className="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                    Batal
                </button>
                <button
                    onClick={onConfirm}
                    disabled={busy}
                    className={`rounded-md px-4 py-2 text-sm font-medium text-white disabled:opacity-60 ${
                        danger
                            ? "bg-red-600 hover:bg-red-700"
                            : "bg-indigo-600 hover:bg-indigo-700"
                    }`}>
                    {busy ? "Memproses..." : confirmLabel}
                </button>
            </div>
        </Modal>
    );
}
