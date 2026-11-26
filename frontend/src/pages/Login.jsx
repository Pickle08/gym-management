import { useState } from "react";
import { Navigate, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthContext";
import { getErrorMessage, getFieldErrors } from "../lib/api";

export default function Login() {
    const { user, login } = useAuth();
    const location = useLocation();
    const [form, setForm] = useState({ email: "", password: "" });
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState("");
    const [fieldErrors, setFieldErrors] = useState({});

    const from = location.state?.from?.pathname || "/";

    if (user) return <Navigate to={from} replace />;

    const onChange = (e) =>
        setForm({ ...form, [e.target.name]: e.target.value });

    const onSubmit = async (e) => {
        e.preventDefault();
        setSubmitting(true);
        setError("");
        setFieldErrors({});

        try {
            await login(form.email, form.password);
        } catch (err) {
            setError(getErrorMessage(err));
            setFieldErrors(getFieldErrors(err));
            setSubmitting(false);
        }
    };

    return (
        <div className="flex min-h-screen items-center justify-center bg-gray-50 px-4">
            <form
                onSubmit={onSubmit}
                className="w-full max-w-sm space-y-4 rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
                <div>
                    <h1 className="text-xl font-bold text-gray-900">
                        Gym Management
                    </h1>
                    <p className="text-sm text-gray-500">
                        Masuk untuk melanjutkan
                    </p>
                </div>

                {error && (
                    <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                        {error}
                    </div>
                )}

                <div>
                    <label
                        className="block text-sm font-medium text-gray-700"
                        htmlFor="email">
                        Email
                    </label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        autoComplete="username"
                        value={form.email}
                        onChange={onChange}
                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                    {fieldErrors.email && (
                        <p className="mt-1 text-xs text-red-600">
                            {fieldErrors.email[0]}
                        </p>
                    )}
                </div>

                <div>
                    <label
                        className="block text-sm font-medium text-gray-700"
                        htmlFor="password">
                        Password
                    </label>
                    <input
                        id="password"
                        name="password"
                        type="password"
                        autoComplete="current-password"
                        value={form.password}
                        onChange={onChange}
                        className="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                    />
                    {fieldErrors.password && (
                        <p className="mt-1 text-xs text-red-600">
                            {fieldErrors.password[0]}
                        </p>
                    )}
                </div>

                <button
                    type="submit"
                    disabled={submitting}
                    className="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60">
                    {submitting ? "Masuk..." : "Masuk"}
                </button>
            </form>
        </div>
    );
}
