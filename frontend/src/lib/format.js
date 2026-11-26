const rupiah = new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    maximumFractionDigits: 0,
});

export const formatRupiah = (n) => rupiah.format(n ?? 0);

// Parse manual (bukan new Date('YYYY-MM-DD')) supaya nggak geser hari karena timezone
export function formatDate(value) {
    if (!value) return "-";
    const [y, m, d] = value.slice(0, 10).split("-").map(Number);

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
    }).format(new Date(y, m - 1, d));
}

export function formatTime(iso) {
    if (!iso) return "-";

    return new Intl.DateTimeFormat("id-ID", {
        hour: "2-digit",
        minute: "2-digit",
        hour12: false,
    }).format(new Date(iso));
}

const compact = new Intl.NumberFormat("id-ID", {
    notation: "compact",
    maximumFractionDigits: 1,
});

export const formatCompact = (n) => compact.format(n ?? 0);

// period dari backend: 'YYYY-MM-DD' (harian) atau 'YYYY-MM' (bulanan)
export function formatPeriod(period, group = "day") {
    const [y, m, d] = period.split("-").map(Number);

    if (group === "month") {
        return new Intl.DateTimeFormat("id-ID", {
            month: "short",
            year: "2-digit",
        }).format(new Date(y, m - 1, 1));
    }

    return new Intl.DateTimeFormat("id-ID", {
        day: "2-digit",
        month: "short",
    }).format(new Date(y, m - 1, d));
}
