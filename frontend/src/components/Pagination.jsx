export default function Pagination({ meta, onPage }) {
    if (!meta || meta.total === 0) return null;

    const {
        current_page: current,
        last_page: last,
        per_page: perPage,
        total,
    } = meta;
    const from = (current - 1) * perPage + 1;
    const to = Math.min(current * perPage, total);

    const btn =
        "rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50";

    return (
        <div className="flex flex-col items-center justify-between gap-2 border-t border-gray-200 px-4 py-3 text-sm text-gray-500 sm:flex-row">
            <span>
                Menampilkan {from}–{to} dari {total}
            </span>
            <div className="flex items-center gap-2">
                <button
                    className={btn}
                    disabled={current <= 1}
                    onClick={() => onPage(current - 1)}>
                    Sebelumnya
                </button>
                <span>
                    {current} / {last}
                </span>
                <button
                    className={btn}
                    disabled={current >= last}
                    onClick={() => onPage(current + 1)}>
                    Berikutnya
                </button>
            </div>
        </div>
    );
}
