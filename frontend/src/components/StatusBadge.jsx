const MAP = {
    active: ["Aktif", "bg-green-50 text-green-700"],
    upcoming: ["Akan datang", "bg-blue-50 text-blue-700"],
    expired: ["Expired", "bg-gray-100 text-gray-600"],
    pending_payment: ["Menunggu bayar", "bg-amber-50 text-amber-700"],
    cancelled: ["Dibatalkan", "bg-red-50 text-red-700"],
};

export default function StatusBadge({ state }) {
    const [label, cls] = MAP[state] ?? [state, "bg-gray-100 text-gray-600"];

    return (
        <span
            className={`whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ${cls}`}>
            {label}
        </span>
    );
}
