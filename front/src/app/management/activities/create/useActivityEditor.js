import { useState } from "react";
import { useToast } from "@/components/ui/Toast";
import { useCreateActivityMutation, useUpdateActivityMutation } from "@/lib/api/activitiesApi";
import { getApiErrorMessage } from "@/lib/apiError";

/**
 * Coordinates activity create and update mutations for the editor page.
 */
export function useActivityEditor({ mode, activityId }) {
  const toast = useToast();
  const [errorMessage, setErrorMessage] = useState("");
  const [createActivity, { isLoading: isCreating }] = useCreateActivityMutation();
  const [updateActivity, { isLoading: isUpdating }] = useUpdateActivityMutation();

  /**
   * Persists the activity and reports whether navigation may continue.
   */
  async function submitActivity(values) {
    setErrorMessage("");

    try {
      if (mode === "edit") {
        await updateActivity({ id: activityId, body: values }).unwrap();
        toast.success("تم تعديل النشاط بنجاح");
      } else {
        await createActivity(values).unwrap();
        toast.success("تم إنشاء النشاط بنجاح");
      }

      return true;
    } catch (error) {
      setErrorMessage(
        getApiErrorMessage(
          error,
          mode === "edit"
            ? "تعذر تعديل النشاط. تحقق من البيانات وحاول مرة أخرى."
            : "تعذر إنشاء النشاط. تحقق من البيانات وحاول مرة أخرى.",
        ),
      );
      return false;
    }
  }

  return {
    errorMessage,
    submitActivity,
    isSubmitting: isCreating || isUpdating,
  };
}
