"use client";

import { useEffect, useState } from "react";
import Button from "@/components/ui/Button";
import Checkbox from "@/components/ui/Checkbox";
import Modal from "@/components/ui/Modal";
import { Field } from "@/components/forms/Field";
import ModificationReasonField from "@/components/forms/ModificationReasonField";
import { doesLockerReleaseRequireReason, getLockerRentalPrice } from "./lockerUtils";

export default function LockerReleaseDialog({ locker, onClose, onConfirm, isLoading = false }) {
  const [reason, setReason] = useState("");
  const [reasonError, setReasonError] = useState("");
  const [isRefund, setIsRefund] = useState(false);
  const [refundAmount, setRefundAmount] = useState("");
  const [refundAmountError, setRefundAmountError] = useState("");
  const requiresReason = doesLockerReleaseRequireReason(locker);

  useEffect(() => {
    setReason("");
    setReasonError("");
    setIsRefund(false);
    setRefundAmount(String(getLockerRentalPrice(locker) ?? ""));
    setRefundAmountError("");
  }, [locker?.id]);

  function submit(event) {
    event.preventDefault();
    const normalizedReason = reason.trim();

    if (requiresReason && !normalizedReason) {
      setReasonError("سبب فك الحجز مطلوب");
      return;
    }

    const normalizedRefundAmount = Number(refundAmount);
    if (
      isRefund &&
      refundAmount !== "" &&
      (!Number.isFinite(normalizedRefundAmount) || normalizedRefundAmount <= 0)
    ) {
      setRefundAmountError("قيمة المبلغ المعاد يجب أن تكون أكبر من صفر");
      return;
    }

    setReasonError("");
    setRefundAmountError("");
    onConfirm({
      reason: normalizedReason,
      is_refund: isRefund,
      ...(isRefund && refundAmount !== "" ? { refund_amount: normalizedRefundAmount } : {}),
    });
  }

  return (
    <Modal
      open={Boolean(locker)}
      onClose={isLoading ? undefined : onClose}
      title="فك الحجز"
      subtitle={`الخزانة ${locker?.locker_number || ""}`}
      className="max-w-md"
    >
      <form className="space-y-5" onSubmit={submit} noValidate>
        <p className="text-sm leading-7 text-app-muted-light">
          هل أنت متأكد من فك حجز الخزانة؟ يجب توضيح سبب فك الحجز قبل المتابعة.
        </p>

        {requiresReason && (
          <ModificationReasonField
            label="سبب فك الحجز *"
            value={reason}
            onChange={(value) => {
              setReason(value);
              if (reasonError) setReasonError("");
            }}
            error={reasonError}
          />
        )}

        {locker && (
          <div className="space-y-4 rounded-xl border border-app-line bg-app-card-soft/70 p-4">
            <Checkbox
              checked={isRefund}
              onChange={(event) => {
                setIsRefund(event.target.checked);
                if (refundAmountError) setRefundAmountError("");
              }}
              label="إعادة مبلغ الإيجار للمشترك"
              disabled={isLoading}
            />

            <p className="text-xs leading-6 text-app-muted-light">
              فعّل هذا الخيار فقط إذا كان الحجز إيجارًا ويجب إعادة المبلغ للمشترك.
            </p>

            {isRefund && (
              <Field
                label="قيمة المبلغ المعاد"
                name="refund_amount"
                type="number"
                value={refundAmount}
                onChange={(event) => {
                  setRefundAmount(event.target.value);
                  if (refundAmountError) setRefundAmountError("");
                }}
                error={refundAmountError}
                min="0.01"
                step="0.01"
                placeholder="اتركه فارغًا لإعادة كامل المبلغ"
                required={false}
                disabled={isLoading}
              />
            )}
          </div>
        )}

        <div className="flex gap-3 border-t border-app-line pt-4">
          <Button
            type="button"
            tone="outline"
            className="h-11 flex-1"
            onClick={onClose}
            disabled={isLoading}
          >
            إلغاء
          </Button>
          <Button type="submit" className="h-11 flex-1 text-black" loading={isLoading}>
            فك الحجز
          </Button>
        </div>
      </form>
    </Modal>
  );
}
