import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import AddMembershipModal from "../components/AddMembershipModal";
import ConfirmModal from "../components/ConfirmModal";
import MemberFormModal from "../components/MemberFormModal";
import PaymentModal from "../components/PaymentModal";
import QrModal from "../components/QrModal";
import StatusBadge from "../components/StatusBadge";
import { useAuth } from "../context/AuthContext";
import api, { getErrorMessage } from "../lib/api";
import { formatDate, formatRupiah } from "../lib/format";

const METHOD_LABEL = { cash: "Tunai", transfer: "Transfer", qris: "QRIS" };

export default function MemberDetail() {
    const { id } = useParams();
    const { user } = useAuth();
    const canWrite = user.role === "admin" || user.role === "staff";

    const [state, setState] = useState({
        member: null,
        memberships: [],
        loading: true,
        error: "",
    });
    const [reloadKey, setReloadKey] = useState(0);
    const [notice, setNotice] = useState(null);
    const [modal, setModal] = useState(null); // { type: 'edit'|'qr'|'add'|'pay'|'cancel', membership? }
    const [busy, setBusy] = useState(false);

    useEffect(() => {
        const controller = new AbortController();
        const { signal } = controller;

        setState((s) => ({ ...s, loading: true, error: "" }));

        // endpoint riwayat membership hanya untuk admin/staff, trainer cukup lihat profil
        const requests = [api.get(`/members/${id}`, { signal })];
        if (canWrite)
            requests.push(api.get(`/members/${id}/memberships`, { signal }));

        Promise.all(requests)
            .then(([m, ms]) =>
                setState({
                    member: m.data.data,
                    memberships: ms ? ms.data.data : [],
                    loading: false,
                    error: "",
                }),
            )
            .catch((err) => {
                if (err.code === "ERR_CANCELED") return;
                setState((s) => ({
                    ...s,
                    loading: false,
                    error: getErrorMessage(err),
                }));
            });

        return () => controller.abort();
    }, [id, canWrite, reloadKey]);

    const reload = () => setReloadKey((k) => k + 1);

    const onSaved = (message) => {
        setModal(null);
        setNotice({ type: "success", text: message });
        reload();
    };

    const cancelMembership = async () => {
        setBusy(true);
        try {
            const res = await api.post(
                `/memberships/${modal.membership.id}/cancel`,
            );
            setNotice({ type: "success", text: res.data.message });
            reload();
        } catch (err) {
            setNotice({ type: "error", text: getErrorMessage(err) });
        } finally {
            setBusy(false);
            setModal(null);
        }
    };

    const { member, memberships, loading, error } = state;

    if (!member && loading)
        return <div className="text-sm text-gray-500">Memuat...</div>;

    if (!member) {
        return (
            <div className="space-y-3">
                <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    {error}
                </div>
                <Link
                    to="/members"
                    className="text-sm text-indigo-600 hover:underline">
                    ← Kembali ke daftar member
                </Link>
            </div>
        );
    }

    // masa aktif terjauh (aktif atau sudah dijadwalkan), dipakai buat hint perpanjangan
    const activeUntil = memberships
        .filter((m) => m.state === "active" || m.state === "upcoming")
        .map((m) => m.end_date)
        .sort()
        .pop();

    const hasPending = memberships.some((m) => m.state === "pending_payment");
    const current = member.current_membership;
    const linkBtn = "text-sm font-medium text-indigo-600 hover:underline";

    return (
        <div className="space-y-4">
            <Link
                to="/members"
                className="text-sm text-gray-500 hover:text-gray-700">
                ← Members
            </Link>

            {notice && (
                <div
                    className={`flex items-start justify-between rounded-md px-3 py-2 text-sm ${
                        notice.type === "success"
                            ? "bg-green-50 text-green-700"
                            : "bg-red-50 text-red-700"
                    }`}>
                    <span>{notice.text}</span>
                    <button
                        onClick={() => setNotice(null)}
                        aria-label="Tutup"
                        className="ml-3">
                        ✕
                    </button>
                </div>
            )}

            {error && (
                <div className="rounded-md bg-red-50 px-3 py-2 text-sm text-red-700">
                    {error}
                </div>
            )}

            {/* Profil */}
            <div className="rounded-xl border border-gray-200 bg-white p-5">
                <div className="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <div className="font-mono text-xs text-gray-500">
                            {member.member_code}
                        </div>
                        <h1 className="text-xl font-semibold text-gray-900">
                            {member.name}
                        </h1>
                        <div className="mt-1">
                            {current ? (
                                <span className="rounded-full bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700">
                                    Aktif s/d {formatDate(current.end_date)}
                                </span>
                            ) : (
                                <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">
                                    Tidak aktif
                                </span>
                            )}
                        </div>
                    </div>

                    {canWrite && (
                        <div className="flex items-center gap-4">
                            <button
                                className={linkBtn}
                                onClick={() => setModal({ type: "qr" })}>
                                QR
                            </button>
                            <button
                                className={linkBtn}
                                onClick={() => setModal({ type: "edit" })}>
                                Edit
                            </button>
                        </div>
                    )}
                </div>

                <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt className="text-gray-500">No. HP</dt>
                        <dd className="text-gray-900">{member.phone || "-"}</dd>
                    </div>
                    <div>
                        <dt className="text-gray-500">Email</dt>
                        <dd className="text-gray-900">{member.email || "-"}</dd>
                    </div>
                    <div>
                        <dt className="text-gray-500">Tanggal lahir</dt>
                        <dd className="text-gray-900">
                            {formatDate(member.birth_date)}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-gray-500">Alamat</dt>
                        <dd className="text-gray-900">
                            {member.address || "-"}
                        </dd>
                    </div>
                </dl>
            </div>

            {/* Membership: hanya admin/staff */}
            {canWrite && (
                <div className="overflow-hidden rounded-xl border border-gray-200 bg-white">
                    <div className="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 px-5 py-3">
                        <h2 className="text-base font-semibold text-gray-900">
                            Riwayat Membership
                        </h2>
                        <button
                            onClick={() => setModal({ type: "add" })}
                            disabled={hasPending}
                            title={
                                hasPending
                                    ? "Selesaikan pembayaran yang tertunda dulu"
                                    : undefined
                            }
                            className="rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                            + {activeUntil ? "Perpanjang" : "Tambah"} Membership
                        </button>
                    </div>

                    {hasPending && (
                        <div className="border-b border-amber-100 bg-amber-50 px-5 py-2 text-sm text-amber-700">
                            Ada membership yang menunggu pembayaran. Bayar atau
                            batalkan dulu sebelum membuat yang baru.
                        </div>
                    )}

                    {memberships.length === 0 ? (
                        <div className="px-5 py-10 text-center text-sm text-gray-500">
                            Belum ada membership.
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-left text-sm">
                                <thead className="bg-gray-50 text-xs uppercase text-gray-500">
                                    <tr>
                                        <th className="px-4 py-3">Plan</th>
                                        <th className="px-4 py-3">Periode</th>
                                        <th className="px-4 py-3">Harga</th>
                                        <th className="px-4 py-3">Status</th>
                                        <th className="px-4 py-3">
                                            Pembayaran
                                        </th>
                                        <th className="px-4 py-3 text-right">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {memberships.map((m) => {
                                        const hasDates =
                                            m.state === "active" ||
                                            m.state === "upcoming" ||
                                            m.state === "expired";

                                        return (
                                            <tr key={m.id}>
                                                <td className="whitespace-nowrap px-4 py-3 font-medium text-gray-900">
                                                    {m.plan?.name}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-gray-600">
                                                    {hasDates ? (
                                                        `${formatDate(m.start_date)} – ${formatDate(m.end_date)}`
                                                    ) : m.state ===
                                                      "pending_payment" ? (
                                                        <span className="text-xs italic text-gray-400">
                                                            Ditentukan saat
                                                            dibayar
                                                        </span>
                                                    ) : (
                                                        "-"
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3">
                                                    {formatRupiah(m.price)}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <StatusBadge
                                                        state={m.state}
                                                    />
                                                </td>
                                                <td className="px-4 py-3 text-xs text-gray-600">
                                                    {m.payments?.length ? (
                                                        m.payments.map((p) => (
                                                            <div key={p.id}>
                                                                {METHOD_LABEL[
                                                                    p.method
                                                                ] ??
                                                                    p.method}{" "}
                                                                ·{" "}
                                                                {formatDate(
                                                                    p.paid_at,
                                                                )}
                                                                {p.received_by &&
                                                                    ` · ${p.received_by}`}
                                                                {p.reference && (
                                                                    <span className="text-gray-400">
                                                                        {" "}
                                                                        (
                                                                        {
                                                                            p.reference
                                                                        }
                                                                        )
                                                                    </span>
                                                                )}
                                                            </div>
                                                        ))
                                                    ) : (
                                                        <span className="text-gray-400">
                                                            -
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-3 text-right">
                                                    {m.state ===
                                                        "pending_payment" && (
                                                        <div className="flex justify-end gap-3">
                                                            <button
                                                                className={
                                                                    linkBtn
                                                                }
                                                                onClick={() =>
                                                                    setModal({
                                                                        type: "pay",
                                                                        membership:
                                                                            m,
                                                                    })
                                                                }>
                                                                Bayar
                                                            </button>
                                                            <button
                                                                className="text-sm font-medium text-red-600 hover:underline"
                                                                onClick={() =>
                                                                    setModal({
                                                                        type: "cancel",
                                                                        membership:
                                                                            m,
                                                                    })
                                                                }>
                                                                Batalkan
                                                            </button>
                                                        </div>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            )}

            {modal?.type === "edit" && (
                <MemberFormModal
                    member={member}
                    onClose={() => setModal(null)}
                    onSaved={onSaved}
                />
            )}

            {modal?.type === "qr" && (
                <QrModal member={member} onClose={() => setModal(null)} />
            )}

            {modal?.type === "add" && (
                <AddMembershipModal
                    member={member}
                    activeUntil={activeUntil}
                    onClose={() => setModal(null)}
                    onSaved={onSaved}
                />
            )}

            {modal?.type === "pay" && (
                <PaymentModal
                    membership={modal.membership}
                    onClose={() => setModal(null)}
                    onSaved={onSaved}
                />
            )}

            {modal?.type === "cancel" && (
                <ConfirmModal
                    title="Batalkan membership?"
                    confirmLabel="Batalkan"
                    danger
                    busy={busy}
                    onConfirm={cancelMembership}
                    onClose={() => setModal(null)}>
                    Membership{" "}
                    <span className="font-medium text-gray-900">
                        {modal.membership.plan?.name}
                    </span>{" "}
                    yang belum dibayar akan dibatalkan.
                </ConfirmModal>
            )}
        </div>
    );
}
