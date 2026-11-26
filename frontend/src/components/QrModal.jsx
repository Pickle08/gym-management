import { QRCodeSVG } from "qrcode.react";
import { useEffect, useState } from "react";
import Modal from "./Modal";
import api, { getErrorMessage } from "../lib/api";

export default function QrModal({ member, onClose }) {
    const [data, setData] = useState(null);
    const [error, setError] = useState("");

    useEffect(() => {
        api.get(`/members/${member.id}/qr`)
            .then((res) => setData(res.data.data))
            .catch((err) => setError(getErrorMessage(err)));
    }, [member.id]);

    return (
        <Modal title="QR Member" onClose={onClose} maxWidth="max-w-sm">
            {error && (
                <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    {error}
                </div>
            )}
            {!error && !data && (
                <div className="text-sm text-gray-500">Memuat...</div>
            )}

            {data && (
                <div className="flex flex-col items-center gap-3">
                    <div className="rounded-lg border border-gray-200 bg-white p-3">
                        <QRCodeSVG value={data.qr_token} size={220} level="M" />
                    </div>
                    <div className="text-center">
                        <div className="font-semibold text-gray-900">
                            {data.name}
                        </div>
                        <div className="text-sm text-gray-500">
                            {data.member_code}
                        </div>
                    </div>
                </div>
            )}
        </Modal>
    );
}
