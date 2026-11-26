import { useState } from "react";
import {
    Bar,
    BarChart,
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from "recharts";
import ChartCard from "../components/ChartCard";
import DateRangeFilter from "../components/DateRangeFilter";
import { useAuth } from "../context/AuthContext";
import useApiData from "../hooks/useApiData";
import { startOfMonth, toISODate } from "../lib/date";
import { formatCompact, formatPeriod, formatRupiah } from "../lib/format";

const INDIGO = "#4f46e5";
const METHOD_LABEL = { cash: "Tunai", transfer: "Transfer", qris: "QRIS" };

function Stat({ label, value }) {
    return (
        <div className="rounded-lg bg-gray-50 px-4 py-3">
            <div className="text-xs text-gray-500">{label}</div>
            <div className="mt-0.5 text-xl font-semibold text-gray-900">
                {value}
            </div>
        </div>
    );
}

function GroupToggle({ value, onChange }) {
    return (
        <div className="flex gap-1 rounded-lg bg-gray-100 p-1">
            {[
                ["day", "Harian"],
                ["month", "Bulanan"],
            ].map(([key, label]) => (
                <button
                    key={key}
                    onClick={() => onChange(key)}
                    className={`rounded-md px-3 py-1 text-sm font-medium ${
                        value === key
                            ? "bg-white text-indigo-700 shadow-sm"
                            : "text-gray-600 hover:text-gray-900"
                    }`}>
                    {label}
                </button>
            ))}
        </div>
    );
}

export default function Reports() {
    const { user } = useAuth();
    const isAdmin = user.role === "admin";

    const [range, setRange] = useState({
        from: toISODate(startOfMonth()),
        to: toISODate(new Date()),
    });
    const [group, setGroup] = useState("day");

    const revenue = useApiData(
        "/reports/revenue",
        { ...range, group },
        isAdmin,
    );
    const attendance = useApiData("/reports/attendance", range);

    const rev = revenue.data;
    const att = attendance.data;

    const avgPerDay =
        att && att.series.length ? att.total_visits / att.series.length : 0;
    const avgPerTx = rev && rev.count ? Math.round(rev.total / rev.count) : 0;

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center justify-between gap-3">
                <h1 className="text-xl font-semibold text-gray-900">Laporan</h1>
                <DateRangeFilter value={range} onChange={setRange} />
            </div>

            {isAdmin && (
                <ChartCard
                    title="Revenue"
                    right={<GroupToggle value={group} onChange={setGroup} />}
                    loading={revenue.loading}
                    error={revenue.error}
                    onRetry={revenue.reload}>
                    {rev && (
                        <div className="space-y-5">
                            <div className="grid gap-3 sm:grid-cols-3">
                                <Stat
                                    label="Total revenue"
                                    value={formatRupiah(rev.total)}
                                />
                                <Stat
                                    label="Jumlah transaksi"
                                    value={rev.count}
                                />
                                <Stat
                                    label="Rata-rata per transaksi"
                                    value={formatRupiah(avgPerTx)}
                                />
                            </div>

                            <div className="h-72">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart
                                        data={rev.series}
                                        margin={{
                                            top: 5,
                                            right: 8,
                                            left: 0,
                                            bottom: 0,
                                        }}>
                                        <CartesianGrid
                                            strokeDasharray="3 3"
                                            vertical={false}
                                        />
                                        <XAxis
                                            dataKey="period"
                                            tickFormatter={(p) =>
                                                formatPeriod(p, rev.group)
                                            }
                                            minTickGap={24}
                                            tick={{ fontSize: 12 }}
                                        />
                                        <YAxis
                                            tickFormatter={formatCompact}
                                            width={56}
                                            tick={{ fontSize: 12 }}
                                        />
                                        <Tooltip
                                            labelFormatter={(p) =>
                                                formatPeriod(p, rev.group)
                                            }
                                            formatter={(v) => [
                                                formatRupiah(v),
                                                "Revenue",
                                            ]}
                                        />
                                        <Bar
                                            dataKey="total"
                                            fill={INDIGO}
                                            radius={[3, 3, 0, 0]}
                                        />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>

                            {rev.count === 0 && (
                                <p className="text-sm text-gray-500">
                                    Belum ada pembayaran pada periode ini.
                                </p>
                            )}

                            {rev.by_method.length > 0 && (
                                <div>
                                    <h3 className="mb-2 text-sm font-medium text-gray-700">
                                        Per metode pembayaran
                                    </h3>
                                    <div className="space-y-2">
                                        {rev.by_method.map((m) => {
                                            const pct = rev.total
                                                ? (m.total / rev.total) * 100
                                                : 0;

                                            return (
                                                <div key={m.method}>
                                                    <div className="flex justify-between text-sm">
                                                        <span className="text-gray-700">
                                                            {METHOD_LABEL[
                                                                m.method
                                                            ] ?? m.method}{" "}
                                                            <span className="text-gray-400">
                                                                ({m.count}{" "}
                                                                transaksi)
                                                            </span>
                                                        </span>
                                                        <span className="font-medium text-gray-900">
                                                            {formatRupiah(
                                                                m.total,
                                                            )}{" "}
                                                            · {pct.toFixed(0)}%
                                                        </span>
                                                    </div>
                                                    <div className="mt-1 h-2 rounded-full bg-gray-100">
                                                        <div
                                                            className="h-2 rounded-full bg-indigo-500"
                                                            style={{
                                                                width: `${pct}%`,
                                                            }}
                                                        />
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </ChartCard>
            )}

            <ChartCard
                title="Kehadiran"
                loading={attendance.loading}
                error={attendance.error}
                onRetry={attendance.reload}>
                {att && (
                    <div className="space-y-5">
                        <div className="grid gap-3 sm:grid-cols-3">
                            <Stat
                                label="Total kunjungan"
                                value={att.total_visits}
                            />
                            <Stat
                                label="Member unik"
                                value={att.unique_members}
                            />
                            <Stat
                                label="Rata-rata per hari"
                                value={avgPerDay.toFixed(1)}
                            />
                        </div>

                        <div className="h-72">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart
                                    data={att.series}
                                    margin={{
                                        top: 5,
                                        right: 8,
                                        left: 0,
                                        bottom: 0,
                                    }}>
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        vertical={false}
                                    />
                                    <XAxis
                                        dataKey="period"
                                        tickFormatter={(p) =>
                                            formatPeriod(p, "day")
                                        }
                                        minTickGap={24}
                                        tick={{ fontSize: 12 }}
                                    />
                                    <YAxis
                                        allowDecimals={false}
                                        width={40}
                                        tick={{ fontSize: 12 }}
                                    />
                                    <Tooltip
                                        labelFormatter={(p) =>
                                            formatPeriod(p, "day")
                                        }
                                        formatter={(v) => [v, "Kunjungan"]}
                                    />
                                    <Line
                                        type="monotone"
                                        dataKey="visits"
                                        stroke={INDIGO}
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </div>
                    </div>
                )}
            </ChartCard>
        </div>
    );
}
