import { useEffect } from "react";

export default function Modal({
    title,
    onClose,
    children,
    maxWidth = "max-w-lg",
}) {
    useEffect(() => {
        const onKey = (e) => e.key === "Escape" && onClose();
        document.addEventListener("keydown", onKey);
        return () => document.removeEventListener("keydown", onKey);
    }, [onClose]);

    return (
        <div
            className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            onMouseDown={(e) => e.target === e.currentTarget && onClose()}>
            <div
                role="dialog"
                aria-modal="true"
                aria-label={title}
                className={`max-h-[90vh] w-full ${maxWidth} overflow-y-auto rounded-xl bg-white shadow-xl`}>
                <div className="flex items-center justify-between border-b border-gray-200 px-5 py-3">
                    <h2 className="text-base font-semibold text-gray-900">
                        {title}
                    </h2>
                    <button
                        type="button"
                        onClick={onClose}
                        aria-label="Tutup"
                        className="text-gray-400 hover:text-gray-600">
                        ✕
                    </button>
                </div>
                <div className="p-5">{children}</div>
            </div>
        </div>
    );
}
