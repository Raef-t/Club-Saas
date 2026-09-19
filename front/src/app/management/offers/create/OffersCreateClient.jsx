"use client";

import { useMemo, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import {
  useCreateOfferMutation,
  useUpdateOfferMutation,
  useGetOfferQuery,
} from "@/lib/api/offersApi";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";
import OfferForm from "../OfferForm";
import OfferEditorHeader from "../_components/OfferEditorHeader";

const FORM_ID = "create-offer-form";

export default function OffersCreateClient() {
  const router = useRouter();
  const toast = useToast();
  const { selectedBranchId } = useManagementBranch();
  const searchParams = useSearchParams();
  const mode = searchParams.get("mode");
  const editId = searchParams.get("id");
  const isEdit = mode === "edit" && Boolean(editId);

  const [formError, setFormError] = useState("");

  const [createOffer, { isLoading: isCreating }] = useCreateOfferMutation();
  const [updateOffer, { isLoading: isUpdating }] = useUpdateOfferMutation();

  const {
    data: offerDetailResponse,
    isLoading: isLoadingOffer,
    error: offerError,
  } = useGetOfferQuery(editId, {
    skip: !isEdit,
  });

  const offerData = useMemo(() => {
    return offerDetailResponse?.data || offerDetailResponse || null;
  }, [offerDetailResponse]);

  async function handleSubmit(values) {
    setFormError("");

    try {
      if (isEdit) {
        await updateOffer({ id: editId, body: values }).unwrap();
        toast.success("تم تعديل العرض الترويجي بنجاح!");
      } else {
        await createOffer(values).unwrap();
        toast.success("تم إنشاء العرض الترويجي بنجاح!");
      }
      router.push("/management/offers");
    } catch (error) {
      const msg = getApiErrorMessage(
        error,
        isEdit ? "تعذر تعديل العرض الترويجي." : "تعذر إنشاء العرض الترويجي.",
      );
      setFormError(msg);
    }
  }

  return (
    <div className="mx-auto w-full max-w-[1180px] space-y-6" dir="rtl">
      <OfferEditorHeader isEdit={isEdit} />

      {isEdit && isLoadingOffer ? (
        <div className="app-card rounded-2xl py-20 text-center text-sm text-app-muted-light">
          جاري تحميل بيانات العرض الترويجي...
        </div>
      ) : isEdit && offerError ? (
        <div className="app-card space-y-3 rounded-2xl py-20 text-center">
          <p className="text-sm text-app-red">تعذر تحميل بيانات العرض المطلوب تعديله.</p>
          <button
            type="button"
            onClick={() => router.push("/management/offers")}
            className="text-xs text-app-yellow underline"
          >
            العودة لقائمة العروض
          </button>
        </div>
      ) : (
        <OfferForm
          key={isEdit ? `offer-edit-${editId}` : `offer-create-${selectedBranchId}`}
          formId={FORM_ID}
          mode={isEdit ? "edit" : "create"}
          initialValues={isEdit ? offerData : null}
          onSubmit={handleSubmit}
          onCancel={() => router.push("/management/offers")}
          isLoading={isEdit ? isUpdating : isCreating}
          errorMessage={formError}
        />
      )}
    </div>
  );
}
