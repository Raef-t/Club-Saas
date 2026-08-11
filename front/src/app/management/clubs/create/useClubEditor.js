"use client";

import { useState } from "react";
import { useToast } from "@/components/ui/Toast";
import { useCreateClubMutation, useUpdateClubMutation } from "@/lib/api/clubsApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { hasDuplicateClubName } from "../clubUtils";

/**
 * Coordinates club validation and mutations for the editor page.
 */
export function useClubEditor({ mode, clubId, existingClubs = [] }) {
  const toast = useToast();
  const [errorMessage, setErrorMessage] = useState("");
  const [createClub, { isLoading: isCreating }] = useCreateClubMutation();
  const [updateClub, { isLoading: isUpdating }] = useUpdateClubMutation();

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
        await updateClub({ id: clubId, body: values }).unwrap();
        toast.success("تم تعديل بيانات النادي بنجاح");
      } else {
        await createClub(values).unwrap();
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
    isSubmitting: isCreating || isUpdating,
  };
}
