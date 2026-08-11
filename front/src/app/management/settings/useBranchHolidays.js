"use client";

import { useEffect, useMemo, useState } from "react";
import { useToast } from "@/components/ui/Toast";
import {
  useCreateBranchHolidayMutation,
  useDeleteBranchHolidayMutation,
  useGetBranchHolidaysQuery,
  useUpdateBranchHolidayMutation,
} from "@/lib/api/branchesApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { getFieldErrors } from "@/lib/validations/formErrors";
import { holidaySchema } from "@/lib/validations/settingsSchema";
import { createHolidayForm, createHolidayPayload, getSettingsCollection } from "./settingsUtils";

/**
 * Coordinates holiday queries, editor state, validation, and deletion.
 */
export function useBranchHolidays({ branchId, initialBranchId, initialHolidays }) {
  const toast = useToast();
  const [editor, setEditor] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [form, setForm] = useState(() => createHolidayForm());
  const [errors, setErrors] = useState({});
  const {
    currentData: holidaysResponse,
    error,
    isLoading,
    isFetching,
    refetch,
  } = useGetBranchHolidaysQuery(branchId, { skip: !branchId });
  const [createHoliday, { isLoading: isCreating }] = useCreateBranchHolidayMutation();
  const [updateHoliday, { isLoading: isUpdating }] = useUpdateBranchHolidayMutation();
  const [deleteHoliday, { isLoading: isDeleting }] = useDeleteBranchHolidayMutation();
  const fallbackHolidays = branchId && branchId === initialBranchId ? initialHolidays : null;
  const holidays = useMemo(
    () => getSettingsCollection(holidaysResponse || fallbackHolidays),
    [fallbackHolidays, holidaysResponse],
  );

  useEffect(() => {
    setEditor(null);
    setDeleteTarget(null);
    setErrors({});
  }, [branchId]);

  /**
   * Updates one holiday field and removes its stale validation error.
   */
  function setField(field, value) {
    setForm((current) => ({ ...current, [field]: value }));
    setErrors((current) => {
      if (!current[field]) return current;
      const updated = { ...current };
      delete updated[field];
      return updated;
    });
  }

  /**
   * Switches the holiday type and clears errors from hidden fields.
   */
  function setHolidayType(value) {
    setForm((current) => ({ ...current, holidayType: value }));
    setErrors({});
  }

  /**
   * Opens an empty holiday editor.
   */
  function openCreate() {
    setForm(createHolidayForm());
    setErrors({});
    setEditor({ mode: "create", holiday: null });
  }

  /**
   * Opens the holiday editor with the selected record.
   */
  function openEdit(holiday) {
    setForm(createHolidayForm(holiday));
    setErrors({});
    setEditor({ mode: "edit", holiday });
  }

  /**
   * Closes the holiday editor and clears validation feedback.
   */
  function closeEditor() {
    setEditor(null);
    setErrors({});
  }

  /**
   * Validates and persists the holiday editor form.
   */
  async function saveHoliday(event) {
    event.preventDefault();
    const validation = holidaySchema.safeParse(form);

    if (!validation.success) {
      setErrors(getFieldErrors(validation.error));
      return;
    }

    try {
      const body = createHolidayPayload(form);

      if (editor?.mode === "edit") {
        await updateHoliday({
          branchId,
          holidayId: editor.holiday.id,
          body,
        }).unwrap();
        toast.success("تم تعديل الإجازة بنجاح");
      } else {
        await createHoliday({ branchId, body }).unwrap();
        toast.success("تمت إضافة الإجازة بنجاح");
      }

      closeEditor();
    } catch (saveError) {
      toast.error(getApiErrorMessage(saveError, "حدث خطأ أثناء حفظ الإجازة."));
    }
  }

  /**
   * Deletes the selected holiday after confirmation.
   */
  async function confirmDelete() {
    if (!deleteTarget) return;

    try {
      await deleteHoliday({
        branchId,
        holidayId: deleteTarget.id,
      }).unwrap();
      toast.success("تم حذف الإجازة بنجاح");
      setDeleteTarget(null);
    } catch (deleteError) {
      toast.error(getApiErrorMessage(deleteError, "حدث خطأ أثناء حذف الإجازة."));
    }
  }

  return {
    holidays,
    isLoading: isLoading && holidays.length === 0,
    isFetching,
    errorMessage: error ? getApiErrorMessage(error, "تعذر تحميل إجازات الفرع.") : "",
    retry: refetch,
    editor,
    form,
    errors,
    setField,
    setHolidayType,
    openCreate,
    openEdit,
    closeEditor,
    saveHoliday,
    isSaving: isCreating || isUpdating,
    deleteTarget,
    setDeleteTarget,
    confirmDelete,
    isDeleting,
  };
}
