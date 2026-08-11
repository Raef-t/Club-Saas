"use client";

import { useEffect, useMemo, useState } from "react";
import { useToast } from "@/components/ui/Toast";
import { useGetBranchSettingsQuery, useUpdateBranchSettingsMutation } from "@/lib/api/branchesApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { getFieldErrors } from "@/lib/validations/formErrors";
import { branchSettingsSchema } from "@/lib/validations/settingsSchema";
import {
  createBranchSettingsForm,
  createBranchSettingsPayload,
  getSettingsRecord,
} from "./settingsUtils";

/**
 * Coordinates loading, validation, and saving for a selected branch's settings.
 */
export function useBranchSettings({ branchId, initialBranchId, initialSettings }) {
  const toast = useToast();
  const initialRecord = useMemo(
    () => (branchId && branchId === initialBranchId ? getSettingsRecord(initialSettings) : null),
    [branchId, initialBranchId, initialSettings],
  );
  const [form, setForm] = useState(() =>
    createBranchSettingsForm(getSettingsRecord(initialSettings)),
  );
  const [formBranchId, setFormBranchId] = useState(initialBranchId || "");
  const [errors, setErrors] = useState({});
  const {
    currentData: settingsResponse,
    error,
    isLoading,
    isFetching,
    refetch,
  } = useGetBranchSettingsQuery(branchId, { skip: !branchId });
  const [updateSettings, { isLoading: isSaving }] = useUpdateBranchSettingsMutation();
  const settingsRecord = getSettingsRecord(settingsResponse) || initialRecord;

  useEffect(() => {
    if (!branchId) {
      setForm(createBranchSettingsForm(null));
      setFormBranchId("");
      setErrors({});
      return;
    }

    if (settingsRecord) {
      setForm(createBranchSettingsForm(settingsRecord));
      setFormBranchId(branchId);
      setErrors({});
      return;
    }

    if (!isLoading && !isFetching) {
      setForm(createBranchSettingsForm(null));
      setFormBranchId(branchId);
      setErrors({});
    }
  }, [branchId, isFetching, isLoading, settingsRecord]);

  /**
   * Updates one branch-settings field and removes its stale validation error.
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
   * Removes a validation error that is controlled outside the settings form.
   */
  function clearError(field) {
    setErrors((current) => {
      if (!current[field]) return current;
      const updated = { ...current };
      delete updated[field];
      return updated;
    });
  }

  /**
   * Validates and saves the current branch settings.
   */
  async function saveSettings(event) {
    event.preventDefault();

    const validation = branchSettingsSchema.safeParse({
      selectedBranchId: branchId,
      ...form,
    });

    if (!validation.success) {
      setErrors(getFieldErrors(validation.error));
      return;
    }

    setErrors({});

    try {
      await updateSettings({
        branchId,
        body: createBranchSettingsPayload(form),
      }).unwrap();
      toast.success("تم حفظ الإعدادات بنجاح");
    } catch (saveError) {
      toast.error(getApiErrorMessage(saveError, "حدث خطأ أثناء حفظ إعدادات الفرع."));
    }
  }

  return {
    form,
    errors,
    setField,
    clearError,
    saveSettings,
    isSaving,
    isLoading:
      Boolean(branchId) &&
      formBranchId !== branchId &&
      (isLoading || isFetching || !settingsRecord),
    errorMessage: error ? getApiErrorMessage(error, "تعذر تحميل إعدادات الفرع.") : "",
    retry: refetch,
  };
}
