import { daysAgo, startOfMonth, toISODate } from "../lib/date";

const today = () => toISODate(new Date());

const PRESETS = [
    {
        label: "7 hari",
        range: () => ({ from: toISODate(daysAgo(6)), to: today() }),
    },
    {
        label: "30 hari",
        range: () => ({ from: toISODate(daysAgo(29)), to: today() }),
    },
    {
        label: "Bulan ini",
        range: () => ({ from: toISODate(startOfMonth()), to: today() }),
    },
    {
        label: "90 hari",
        range: () => ({ from: toISODate(daysAgo(89)), to: today() }),
    },
];

export default function DateRangeFilter({ value, onChange }) {
    // jaga from <= to supaya nggak kena 422 dari backend
    const setFrom = (from) =>
        from && onChange({ from, to: value.to < from ? from : value.to });
    const setTo = (to) =>
        to && onChange({ from: value.from > to ? to : value.from, to });

    const input =
        "rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500";

    return (
        <div className="flex flex-wrap items-center gap-2">
            <div className="flex gap-1 rounded-lg bg-gray-100 p-1">
                {PRESETS.map((p) => {
                    const r = p.range();
                    const active = r.from === value.from && r.to === value.to;

                    return (
                        <button
                            key={p.label}
                            onClick={() => onChange(r)}
                            className={`rounded-md px-3 py-1 text-sm font-medium ${
                                active
                                    ? "bg-white text-indigo-700 shadow-sm"
                                    : "text-gray-600 hover:text-gray-900"
                            }`}>
                            {p.label}
                        </button>
                    );
                })}
            </div>

            <div className="flex items-center gap-2 text-sm text-gray-500">
                <input
                    type="date"
                    value={value.from}
                    max={today()}
                    onChange={(e) => setFrom(e.target.value)}
                    className={input}
                />
                <span>s/d</span>
                <input
                    type="date"
                    value={value.to}
                    max={today()}
                    onChange={(e) => setTo(e.target.value)}
                    className={input}
                />
            </div>
        </div>
    );
}
