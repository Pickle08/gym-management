export default function ChartCard({
    title,
    right,
    loading,
    error,
    onRetry,
    children,
}) {
    return (
        <section className="rounded-xl border border-gray-200 bg-white">
            <div className="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-5 py-3">
                <h2 className="text-base font-semibold text-gray-900">
                    {title}
                </h2>
                {right}
            </div>

            <div className="p-5">
                {error && (
                    <div className="flex items-center justify-between rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                        <span>{error}</span>
                        <button
                            onClick={onRetry}
                            className="font-medium underline">
                            Coba lagi
                        </button>
                    </div>
                )}

                {!children && loading && (
                    <div className="text-sm text-gray-500">Memuat...</div>
                )}

                {children && (
                    <div className={loading ? "opacity-50" : ""}>
                        {children}
                    </div>
                )}
            </div>
        </section>
    );
}
