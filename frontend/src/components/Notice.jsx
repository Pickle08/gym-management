export default function Notice({ notice, onClose }) {
    if (!notice) return null;
    const ok = notice.type === "success";

    return (
        <div
            role={ok ? "status" : "alert"}
            className={`flex items-start justify-between rounded-md px-3 py-2 text-sm ${
                ok ? "bg-green-50 text-green-700" : "bg-red-50 text-red-700"
            }`}>
            <span>{notice.text}</span>
            <button onClick={onClose} aria-label="Tutup" className="ml-3">
                ✕
            </button>
        </div>
    );
}
