"use client";

import { useState, useEffect } from "react";
import ConfirmDialog from "@/components/ui/ConfirmDialog";
import { useDeleteOfferMutation } from "@/lib/api/offersApi";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";

export default function DeleteOfferModal({ open, onClose, offer }) {
  const toast = useToast();
  const [deleteOffer, { isLoading }] = useDeleteOfferMutation();
  const [confirmationInput, setConfirmationInput] = useState("");
  const [error, setError] = useState("");

  useEffect(() => {
    if (open) {
      setConfirmationInput("");
      setError("");
    }
  }, [open]);

  if (!offer) return null;

  const activeCount = offer.active_subscribers_count || 0;
  const hasActiveSubscribers = activeCount > 0;

  async function handleConfirm() {
    setError("");
    try {
      await deleteOffer({ id: offer.id, confirm: "delete" }).unwrap();
      toast.success("تم حذف العرض الترويجي بنجاح.");
      onClose();
    } catch (err) {
      setError(getApiErrorMessage(err, "تعذر حذف العرض. يرجى كتابة delete والتأكيد."));
    }
  }

  return (
    <ConfirmDialog
      open={open}
      onClose={onClose}
      onConfirm={handleConfirm}
      isLoading={isLoading}
      title={`حذف العرض: ${offer.name}`}
      message={
        hasActiveSubscribers
          ? `⚠️ تنبيه هام: يوجد حالياً ${activeCount} اشتراك(ات) نشطة لهذا العرض. حذف العرض سيؤدي إلى إلغاء وحذف اشتراكاتهم المقترنة. يرجى كتابة "delete" للتأكيد النهائي.`
          : "هل أنت متأكد من رغبتك في حذف هذا العرض الترويجي؟ يمكنك استرجاعه لاحقاً من سلة المهملات."
      }
      requiredConfirmation="delete"
      confirmationValue={confirmationInput}
      onConfirmationChange={setConfirmationInput}
      confirmationLabel="اكتب كلمة delete للتأكيد النهائي:"
      confirmLabel="تأكيد الحذف"
      cancelLabel="تراجع"
      tone="danger"
    >
      {error && (
        <div className="rounded-lg border border-app-red/40 bg-app-red/10 p-2.5 text-xs text-app-red text-right">
          {error}
        </div>
      )}
    </ConfirmDialog>
  );
}
