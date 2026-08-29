import { useState } from "react";
import { useToast } from "@/components/ui/Toast";
import {
  useCreateClubMutation,
  useUpdateClubLogoMutation,
  useUpdateClubMutation,
} from "@/lib/api/clubsApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { createClubFormData, hasDuplicateClubName } from "../clubUtils";

/**
 * Coordinates club validation and mutations for the editor page.
 */
export function useClubEditor({ mode, clubId, existingClubs = [] }) {
  const toast = useToast();
  const [errorMessage, setErrorMessage] = useState("");
  const [createClub, { isLoading: isCreating }] = useCreateClubMutation();
  const [updateClub, { isLoading: isUpdating }] = useUpdateClubMutation();
  const [updateClubLogo, { isLoading: isUpdatingLogo }] = useUpdateClubLogoMutation();

  /**
   * Persists the club and reports whether navigation may continue.
   */
  async function submitClub(values) {
    setErrorMessage("");

    if (hasDuplicateClubName(existingClubs, values.name, mode === "edit" ? clubId : null)) {
      setErrorMessage("اسم النادي موجود بالفعل، يرجى اختيار اسم آخر.");
      return false;
    }

    try {
      if (mode === "edit") {
        const payload =
          values instanceof FormData ? values : createClubFormData(values, { includeLogo: false });
        await updateClub({ id: clubId, body: payload }).unwrap();
        if (values.logo instanceof File) {
          await updateClubLogo({ id: clubId, logo: values.logo }).unwrap();
        }
        toast.success("تم تعديل بيانات النادي بنجاح");
      } else {
        const payload = values instanceof FormData ? values : createClubFormData(values);
        await createClub(payload).unwrap();
        toast.success("تم إنشاء النادي بنجاح");
      }

      return true;
    } catch (error) {
      setErrorMessage(
        getApiErrorMessage(
          error,
          mode === "edit"
            ? "تعذر تعديل النادي. تحقق من البيانات وحاول مرة أخرى."
            : "تعذر إنشاء النادي. تحقق من البيانات وحاول مرة أخرى.",
        ),
      );
      return false;
    }
  }

  return {
    errorMessage,
    submitClub,
    isSubmitting: isCreating || isUpdating || isUpdatingLogo,
  };
}
