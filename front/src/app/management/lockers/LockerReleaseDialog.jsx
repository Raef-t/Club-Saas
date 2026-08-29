"use client";

import { useEffect, useState } from "react";
import Button from "@/components/ui/Button";
import Modal from "@/components/ui/Modal";
import ModificationReasonField from "@/components/forms/ModificationReasonField";
import { doesLockerReleaseRequireReason } from "./lockerUtils";

export default function LockerReleaseDialog({ locker, onClose, onConfirm, isLoading = false }) {
  const [reason, setReason] = useState("");
  const [reasonError, setReasonError] = useState("");
  const requiresReason = doesLockerReleaseRequireReason(locker);

  useEffect(() => {
    setReason("");
    setReasonError("");
  }, [locker?.id]);

  function submit(event) {
    event.preventDefault();
    const normalizedReason = reason.trim();

    if (requiresReason && !normalizedReason) {
      setReasonError("سبب فك الحجز المبكر مطلوب");
      return;
    }

    setReasonError("");
    onConfirm(requiresReason ? normalizedReason : undefined);
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
          هل أنت متأكد من فك حجز الخزانة؟
          {requiresReason && " ما زال الحجز ساريًا، لذلك يجب توضيح سبب الفك المبكر."}
        </p>

        {requiresReason && (
          <ModificationReasonField
            label="سبب فك الحجز المبكر *"
            value={reason}
            onChange={(value) => {
              setReason(value);
              if (reasonError) setReasonError("");
            }}
            error={reasonError}
          />
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
