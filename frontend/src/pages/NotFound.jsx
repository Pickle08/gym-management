import { Link } from "react-router-dom";

export default function NotFound() {
    return (
        <div className="p-8">
            <h1 className="text-lg font-semibold text-gray-900">
                Halaman tidak ditemukan
            </h1>
            <Link
                to="/"
                className="mt-2 inline-block text-sm text-indigo-600 hover:underline">
                Kembali ke dashboard
            </Link>
        </div>
    );
}
