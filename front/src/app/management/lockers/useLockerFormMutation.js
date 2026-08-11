import { useState } from "react";
import { useCreateLockerMutation, useUpdateLockerMutation } from "@/lib/api/lockersApi";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";

/**
 * Coordinates locker creation and exposes a form-safe backend error.
 */
export function useCreateLocker() {
  const toast = useToast();
  const [formError, setFormError] = useState("");
  const [createLocker, { isLoading }] = useCreateLockerMutation();

  /**
   * Creates a locker and reports whether navigation may continue.
   */
  async function submitLocker(values) {
    setFormError("");

    try {
      await createLocker(values).unwrap();
      toast.success("تمت إضافة الخزانة بنجاح!");
      return true;
    } catch (error) {
      setFormError(
        getApiErrorMessage(error, "تعذر إضافة الخزانة. تحقق من البيانات وحاول مرة أخرى."),
      );
      return false;
    }
  }

  return { formError, isLoading, submitLocker };
}

/**
 * Coordinates locker updates and exposes a form-safe backend error.
 */
export function useUpdateLocker(lockerId) {
  const toast = useToast();
  const [formError, setFormError] = useState("");
  const [updateLocker, { isLoading }] = useUpdateLockerMutation();

  /**
   * Updates the current locker and reports whether navigation may continue.
   */
  async function submitLocker(values) {
    setFormError("");

    try {
      await updateLocker({ id: lockerId, ...values }).unwrap();
      toast.success("تم تعديل الخزانة بنجاح!");
      return true;
    } catch (error) {
      setFormError(getApiErrorMessage(error, "تعذر تعديل الخزانة."));
      return false;
    }
  }

  return { formError, isLoading, submitLocker };
}
