import { useState } from "react";
import { useToast } from "@/components/ui/Toast";
import { useCreateBranchMutation, useUpdateBranchMutation } from "@/lib/api/branchesApi";
import { getApiErrorMessage } from "@/lib/apiError";

/**
 * Coordinates branch create and update mutations for the editor page.
 */
export function useBranchEditor({ mode, branchId }) {
  const toast = useToast();
  const [errorMessage, setErrorMessage] = useState("");
  const [createBranch, { isLoading: isCreating }] = useCreateBranchMutation();
  const [updateBranch, { isLoading: isUpdating }] = useUpdateBranchMutation();

  /**
   * Persists the branch and reports whether navigation may continue.
   */
  async function submitBranch(values) {
    setErrorMessage("");

    try {
      if (mode === "edit") {
        await updateBranch({ id: branchId, body: values }).unwrap();
        toast.success("تم تعديل بيانات الفرع بنجاح");
      } else {
        await createBranch(values).unwrap();
        toast.success("تم إنشاء الفرع بنجاح");
      }

      return true;
    } catch (error) {
      setErrorMessage(
        getApiErrorMessage(
          error,
          mode === "edit"
            ? "تعذر تعديل الفرع. تحقق من البيانات وحاول مرة أخرى."
            : "تعذر إنشاء الفرع. تحقق من البيانات وحاول مرة أخرى.",
        ),
      );
      return false;
    }
  }

  return {
    errorMessage,
    submitBranch,
    isSubmitting: isCreating || isUpdating,
  };
}
