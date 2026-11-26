import { useEffect, useState } from "react";
import { useAuth } from "../context/AuthContext";
import api, { getErrorMessage } from "../lib/api";
import { formatRupiah } from "../lib/format";
import { Navigate } from "react-router-dom";

function Card({ label, value }) {
    return (
        <div className="rounded-xl border border-gray-200 bg-white p-4">
            <div className="text-sm text-gray-500">{label}</div>
            <div className="mt-1 text-2xl font-semibold text-gray-900">
                {value}
            </div>
        </div>
    );
}

export default function Dashboard() {
    const { user } = useAuth();
    const canSeeSummary = user.role === "admin" || user.role === "staff";

    const [data, setData] = useState(null);
    const [error, setError] = useState("");

    useEffect(() => {
        if (!canSeeSummary) return;

        api.get("/dashboard/summary")
            .then((res) => setData(res.data.data))
            .catch((err) => setError(getErrorMessage(err)));
    }, [canSeeSummary]);

    if (!canSeeSummary) return <Navigate to="/members" replace />;

    if (error)
        return (
            <div className="rounded-md bg-red-50 p-3 text-sm text-red-700">
                {error}
            </div>
        );
    if (!data) return <div className="text-sm text-gray-500">Memuat...</div>;

    return (
        <div className="space-y-4">
            <h1 className="text-xl font-semibold text-gray-900">Dashboard</h1>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <Card label="Check-in hari ini" value={data.today_checkins} />
                <Card label="Member aktif" value={data.active_members} />
                <Card
                    label="Member baru bulan ini"
                    value={data.new_members_this_month}
                />
                <Card
                    label="Menunggu pembayaran"
                    value={data.pending_payments}
                />
                <Card
                    label={`Hampir expired (${data.expiring_days} hari)`}
                    value={data.expiring_soon}
                />
                {data.revenue_this_month !== null && (
                    <Card
                        label="Revenue bulan ini"
                        value={formatRupiah(data.revenue_this_month)}
                    />
                )}
            </div>
        </div>
    );
}
