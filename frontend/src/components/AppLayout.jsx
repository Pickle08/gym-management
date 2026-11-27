import { NavLink, Outlet } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { useState } from "react";
import ChangePasswordModal from "./ChangePasswordModal";

// Tambah item di sini tiap halaman baru jadi. roles = siapa yang boleh lihat menu itu.
const NAV = [
    {
        to: "/",
        label: "Dashboard",
        roles: ["admin", "staff", "trainer"],
        end: true,
    },
    { to: "/members", label: "Members", roles: ["admin", "staff", "trainer"] },
    { to: "/check-in", label: "Check-in", roles: ["admin", "staff"] },
    { to: "/reports", label: "Laporan", roles: ["admin", "staff"] },
    { to: "/plans", label: "Plans", roles: ["admin"] },
    { to: "/users", label: "Users", roles: ["admin"] },
];

export default function AppLayout() {
    const { user, logout } = useAuth();
    const items = NAV.filter((item) => item.roles.includes(user.role));
    const [showPassword, setShowPassword] = useState(false);

    const linkClass = ({ isActive }) =>
        `whitespace-nowrap rounded-md px-3 py-2 text-sm font-medium ${
            isActive
                ? "bg-indigo-50 text-indigo-700"
                : "text-gray-600 hover:bg-gray-100"
        }`;

    return (
        <div className="flex min-h-screen flex-col bg-gray-50 md:flex-row">
            <aside className="border-b border-gray-200 bg-white md:w-56 md:border-b-0 md:border-r">
                <div className="px-4 py-4 text-lg font-bold text-indigo-600">
                    Gym Management
                </div>
                <nav className="flex gap-1 overflow-x-auto px-2 pb-2 md:flex-col md:pb-0">
                    {items.map((item) => (
                        <NavLink
                            key={item.to}
                            to={item.to}
                            end={item.end}
                            className={linkClass}>
                            {item.label}
                        </NavLink>
                    ))}
                </nav>
            </aside>

            <div className="flex min-w-0 flex-1 flex-col">
                <header className="flex items-center justify-between border-b border-gray-200 bg-white px-4 py-3">
                    <div className="text-sm text-gray-500">
                        <span className="font-medium text-gray-900">
                            {user.name}
                        </span>
                        <span className="ml-2 rounded bg-gray-100 px-2 py-0.5 text-xs uppercase">
                            {user.role}
                        </span>
                    </div>
                    <div className="flex items-center gap-2">
                        <button
                            onClick={() => setShowPassword(true)}
                            className="px-2 py-1.5 text-sm text-gray-600 hover:text-gray-900">
                            Ganti password
                        </button>
                        <button
                            onClick={logout}
                            className="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50">
                            Logout
                        </button>
                    </div>
                </header>

                <main className="flex-1 p-4 md:p-6">
                    <Outlet />
                </main>
            </div>
            {showPassword && (
                <ChangePasswordModal onClose={() => setShowPassword(false)} />
            )}
        </div>
    );
}
