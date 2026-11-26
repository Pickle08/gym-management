import { Html5Qrcode } from "html5-qrcode";
import { useEffect, useRef, useState } from "react";

const ELEMENT_ID = "qr-reader";

export default function QrScanner({ onScan }) {
    const [error, setError] = useState("");
    const onScanRef = useRef(onScan);

    // selalu pakai callback terbaru tanpa restart kamera
    useEffect(() => {
        onScanRef.current = onScan;
    });

    useEffect(() => {
        let scanner;
        let startPromise;

        // setTimeout: StrictMode di dev mount-unmount-mount, tanpa ini kamera di-start dua kali
        const timer = setTimeout(() => {
            scanner = new Html5Qrcode(ELEMENT_ID);
            startPromise = scanner.start(
                { facingMode: "environment" },
                { fps: 10, qrbox: { width: 240, height: 240 } },
                (text) => onScanRef.current(text),
                () => {},
            );
            startPromise.catch(() =>
                setError(
                    "Kamera tidak bisa dibuka. Cek izin kamera di browser, atau pakai mode lain.",
                ),
            );
        }, 0);

        return () => {
            clearTimeout(timer);
            if (startPromise) {
                startPromise
                    .then(() => scanner.stop())
                    .then(() => scanner.clear())
                    .catch(() => {});
            }
        };
    }, []);

    return (
        <div>
            <div
                id={ELEMENT_ID}
                className="mx-auto w-full max-w-sm overflow-hidden rounded-lg bg-gray-100"
            />
            {error && (
                <div className="mt-3 rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    {error}
                </div>
            )}
            {!error && (
                <p className="mt-2 text-center text-xs text-gray-500">
                    Arahkan QR member ke kamera. Browser akan minta izin kamera.
                </p>
            )}
        </div>
    );
}
