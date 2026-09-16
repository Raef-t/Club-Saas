"use client";

import { useEffect, useMemo, useState } from "react";
import Modal from "@/components/ui/Modal";
import Button from "@/components/ui/Button";
import Dropdown from "@/components/ui/Dropdown";
import DatePickerSmart from "@/components/forms/DatePickerSmart";
import { useGetMembersQuery } from "@/lib/api/membersApi";
import { useSubscribeToOfferMutation } from "@/lib/api/offersApi";
import { useToast } from "@/components/ui/Toast";
import { formatMoney } from "@/lib/utils";
import { getApiErrorMessage } from "@/lib/apiError";
import { withAllItems } from "@/lib/pagination";

const PAYMENT_METHODS = [
  { value: "cash", label: "نقداً (كاش)" },
  { value: "card", label: "بطاقة ائتمان / مدى" },
  { value: "wallet", label: "المحفظة الإلكترونية" },
  { value: "bank_transfer", label: "تحويل بنكي" },
];

export default function SubscribeOfferModal({ open, onClose, offer }) {
  const toast = useToast();
  const [subscribeToOffer, { isLoading: isSubmitting }] = useSubscribeToOfferMutation();

  const [form, setForm] = useState({
    member_id: "",
    paid_amount: "",
    payment_method: "cash",
    receipt_number: "",
    start_date: new Date().toISOString().split("T")[0],
    notes: "",
  });

  const [errors, setErrors] = useState({});
  const [generalError, setGeneralError] = useState("");

  const branchParam = offer?.branch_id ? { branch_id: offer.branch_id } : {};
  const { data: membersData, isLoading: isMembersLoading } = useGetMembersQuery(
    withAllItems(branchParam),
    { skip: !open }
  );

  const members = useMemo(() => {
    let list = [];
    if (Array.isArray(membersData?.data?.data)) list = membersData.data.data;
    else if (Array.isArray(membersData?.data)) list = membersData.data;
    else if (Array.isArray(membersData)) list = membersData;
    return list;
  }, [membersData]);

  useEffect(() => {
    if (!open || !offer) return;

    setForm({
      member_id: "",
      paid_amount: offer.price !== undefined ? String(offer.price) : "0",
      payment_method: "cash",
      receipt_number: "",
      start_date: new Date().toISOString().split("T")[0],
      notes: "",
    });

    setErrors({});
    setGeneralError("");
  }, [open, offer]);

  function updateField(key, value) {
    setForm((prev) => ({ ...prev, [key]: value }));
    if (errors[key]) {
      setErrors((prev) => ({ ...prev, [key]: null }));
    }
  }

  const remainingAmount = useMemo(() => {
    const total = Number(offer?.price) || 0;
    const paid = Number(form.paid_amount) || 0;
    return Math.max(0, total - paid);
  }, [offer?.price, form.paid_amount]);

  async function handleSubmit(e) {
    e.preventDefault();
    setGeneralError("");

    const newErrors = {};
    if (!form.member_id) newErrors.member_id = "يرجى اختيار اللاعب المشترك.";
    if (form.paid_amount === "" || Number(form.paid_amount) < 0) {
      newErrors.paid_amount = "يرجى إدخال مبلغ دفع صالح.";
    }

    if (Object.keys(newErrors).length > 0) {
      setErrors(newErrors);
      return;
    }

    const payload = {
      member_id: Number(form.member_id),
      paid_amount: Number(form.paid_amount),
      payment_method: form.payment_method,
      receipt_number: form.receipt_number.trim() || null,
      start_date: form.start_date || null,
      notes: form.notes.trim() || null,
    };

    try {
      await subscribeToOffer({ id: offer.id, body: payload }).unwrap();
      toast.success(`تم اشتراك اللاعب في عرض "${offer.name}" وتوليد الفاتورة والاشتراكات بنجاح! 🎉`);
      onClose();
    } catch (err) {
      setGeneralError(getApiErrorMessage(err, "تعذر إتمام عملية الاشتراك. تحقق من البيانات وحاول مرة أخرى."));
    }
  }

  if (!offer) return null;

  return (
    <Modal
      open={open}
      onClose={onClose}
      title="اشتراك لاعب في عرض ترويجي"
      subtitle={`الباقة: ${offer.name} (${formatMoney(offer.price)})`}
      className="max-w-xl"
    >
      <form onSubmit={handleSubmit} className="space-y-4 text-right" dir="rtl">
        {generalError && (
          <div className="rounded-xl border border-app-red/40 bg-app-red/10 p-3 text-xs text-app-red leading-relaxed">
            {generalError}
          </div>
        )}

        {/* Offer Summary Banner */}
        <div className="rounded-xl border border-cyan-500/30 bg-cyan-950/20 p-3.5 space-y-2 text-xs">
          <div className="flex justify-between items-center text-cyan-200">
            <span className="font-semibold text-sm text-white">{offer.name}</span>
            <span className="font-bold text-app-yellow text-sm">{formatMoney(offer.price)}</span>
          </div>
          <div className="text-cyan-300/80">
            <span>الخطط المشمولة: </span>
            <span className="font-medium text-white">
              {offer.plans?.map((p) => p.name).join(" + ") || "لا توجد خطط"}
            </span>
          </div>
          {offer.available_slots !== null && (
            <div className="text-[11px] text-cyan-400">
              المقاعد المتاحة في الباقة: <strong>{offer.available_slots} مقعد</strong>
            </div>
          )}
        </div>

        {/* Member selection */}
        <label className="block text-right text-xs font-medium text-app-muted-light">
          اللاعب العضو <span className="text-app-red">*</span>
          <Dropdown
            searchable
            searchPlaceholder="ابحث باسم اللاعب أو رقم العضوية..."
            className="mt-1.5 text-white"
            buttonClassName="bg-app-card-soft h-10"
            value={String(form.member_id)}
            onChange={(val) => updateField("member_id", val)}
            options={members.map((m) => ({
              value: String(m.id),
              label: `${m.person?.full_name || m.username || `عضو #${m.id}`} (عضوية #${m.member_number || m.id})`,
            }))}
            placeholder={isMembersLoading ? "جاري تحميل اللاعبين..." : "اختر اللاعب"}
            error={errors.member_id}
          />
          {errors.member_id && <span className="mt-1 block text-xs text-app-red">{errors.member_id}</span>}
        </label>

        {/* Start Date */}
        <div>
          <DatePickerSmart
            label="تاريخ بدء الاشتراك"
            value={form.start_date}
            onChange={(val) => updateField("start_date", val)}
            compact={true}
            placeholder="DD/MM/YYYY"
          />
        </div>

        {/* Payment details */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <label className="block text-right text-xs font-medium text-app-muted-light">
            المبلغ المدفوع حالياً <span className="text-app-red">*</span>
            <input
              type="number"
              min="0"
              step="any"
              value={form.paid_amount}
              onChange={(e) => updateField("paid_amount", e.target.value)}
              placeholder="0.00"
              className={`app-input mt-1.5 h-10 w-full px-3 text-sm font-semibold text-emerald-400 ${
                errors.paid_amount ? "border-app-red" : ""
              }`}
            />
            {errors.paid_amount && (
              <span className="mt-1 block text-xs text-app-red">{errors.paid_amount}</span>
            )}
          </label>

          <label className="block text-right text-xs font-medium text-app-muted-light">
            طريقة الدفع
            <Dropdown
              className="mt-1.5 text-white"
              buttonClassName="bg-app-card-soft h-10"
              value={form.payment_method}
              onChange={(val) => updateField("payment_method", val)}
              options={PAYMENT_METHODS}
            />
          </label>
        </div>

        {/* Remaining amount badge */}
        {remainingAmount > 0 ? (
          <div className="rounded-lg bg-amber-500/10 border border-amber-500/30 p-2.5 flex justify-between text-xs text-amber-400">
            <span>المبلغ المتبقي كذمة على اللاعب:</span>
            <span className="font-bold">{formatMoney(remainingAmount)}</span>
          </div>
        ) : (
          <div className="rounded-lg bg-emerald-500/10 border border-emerald-500/30 p-2.5 flex justify-between text-xs text-emerald-400">
            <span>حالة السداد:</span>
            <span className="font-bold">مدفوع بالكامل ✅</span>
          </div>
        )}

        {/* Receipt number & Notes */}
        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <label className="block text-right text-xs font-medium text-app-muted-light">
            رقم إيصال القبض (اختياري)
            <input
              type="text"
              value={form.receipt_number}
              onChange={(e) => updateField("receipt_number", e.target.value)}
              placeholder="REC-2026-001"
              className="app-input mt-1.5 h-10 w-full px-3 text-xs text-white"
            />
          </label>

          <label className="block text-right text-xs font-medium text-app-muted-light">
            ملاحظات إضافية (اختياري)
            <input
              type="text"
              value={form.notes}
              onChange={(e) => updateField("notes", e.target.value)}
              placeholder="أي ملاحظات تخص الاشتراك..."
              className="app-input mt-1.5 h-10 w-full px-3 text-xs text-white"
            />
          </label>
        </div>

        {/* Actions */}
        <div className="flex gap-3 pt-3 border-t border-app-line">
          <Button
            type="submit"
            tone="primary"
            className="flex-1 h-10 text-sm font-semibold"
            loading={isSubmitting}
          >
            تأكيد وتسجيل الاشتراك 🎉
          </Button>
          <Button
            type="button"
            tone="outline"
            className="h-10 px-5 text-sm"
            onClick={onClose}
            disabled={isSubmitting}
          >
            إلغاء
          </Button>
        </div>
      </form>
    </Modal>
  );
}
