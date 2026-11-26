import { Navigate, Outlet, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export default function ProtectedRoute({ roles }) {
    const { user, loading } = useAuth();
    const location = useLocation();

    if (loading) {
        return <div className="p-8 text-sm text-gray-500">Memuat...</div>;
    }

    if (!user) {
        return <Navigate to="/login" replace state={{ from: location }} />;
    }

    if (roles && !roles.includes(user.role)) {
        return (
            <div className="p-8">
                <h1 className="text-lg font-semibold text-gray-900">
                    Akses ditolak
                </h1>
                <p className="mt-1 text-sm text-gray-500">
                    Role Anda tidak punya akses ke halaman ini.
                </p>
            </div>
        );
    }

    return <Outlet />;
}
