"use client";

import { useMemo, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import ManagementCreatePage from "@/components/forms/ManagementCreatePage";
import { FormCard } from "@/components/forms/FormControls";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import {
  useCreateOfferMutation,
  useUpdateOfferMutation,
  useGetOfferQuery,
} from "@/lib/api/offersApi";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";
import OfferForm from "../OfferForm";

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
        isEdit ? "تعذر تعديل العرض الترويجي." : "تعذر إنشاء العرض الترويجي."
      );
      setFormError(msg);
    }
  }

  return (
    <ManagementCreatePage
      title={isEdit ? "تعديل عرض ترويجي" : "إضافة عرض ترويجي"}
      subtitle={
        isEdit
          ? "العروض الترويجية > تعديل عرض ترويجي"
          : "العروض الترويجية > إضافة عرض ترويجي جديد"
      }
      formId={FORM_ID}
      backHref="/management/offers"
      isSubmitting={isEdit ? isUpdating : isCreating}
      submitLabel={isEdit ? "حفظ التعديل" : "إنشاء العرض"}
    >
      <FormCard title="تفاصيل العرض الترويجي" className="entry-form-card p-5">
        {isEdit && isLoadingOffer ? (
          <p className="py-8 text-center text-sm text-app-muted-light">
            جاري تحميل بيانات العرض الترويجي...
          </p>
        ) : isEdit && offerError ? (
          <div className="py-8 text-center space-y-3">
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
      </FormCard>
    </ManagementCreatePage>
  );
}
