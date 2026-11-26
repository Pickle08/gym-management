const pad = (n) => String(n).padStart(2, "0");

// Pakai komponen lokal, bukan toISOString(), supaya nggak geser hari karena UTC
export const toISODate = (d) =>
    `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;

export function daysAgo(n) {
    const d = new Date();
    d.setDate(d.getDate() - n);
    return d;
}

export function startOfMonth() {
    const d = new Date();
    d.setDate(1);
    return d;
}
