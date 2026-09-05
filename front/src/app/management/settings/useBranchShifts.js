import { useEffect, useMemo, useState } from "react";
import { useToast } from "@/components/ui/Toast";
import {
  useCreateBranchShiftMutation,
  useDeleteBranchShiftMutation,
  useGetBranchShiftsQuery,
  useUpdateBranchShiftMutation,
} from "@/lib/api/branchesApi";
import { getApiErrorMessage } from "@/lib/apiError";
import { getFieldErrors } from "@/lib/validations/formErrors";
import { shiftSchema } from "@/lib/validations/settingsSchema";
import { getGenderForBranchId } from "@/lib/managementBranchUtils";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { printBranchShifts } from "./shiftPrint";
import {
  createShiftForm,
  createShiftPayload,
  getBranchName,
  getSettingsCollection,
} from "./settingsUtils";

/**
 * Coordinates shift queries, editor state, printing, and destructive actions.
 */
export function useBranchShifts({
  branchId,
  branches,
  formatTime,
  initialBranchId,
  initialShifts,
}) {
  const toast = useToast();
  const { brandLogoUrl } = useManagementBranch();
  const [editor, setEditor] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const [form, setForm] = useState(() => createShiftForm());
  const [errors, setErrors] = useState({});
  const {
    currentData: shiftsResponse,
    error,
    isLoading,
    isFetching,
    refetch,
  } = useGetBranchShiftsQuery(branchId, { skip: !branchId });
  const [createShift, { isLoading: isCreating }] = useCreateBranchShiftMutation();
  const [updateShift, { isLoading: isUpdating }] = useUpdateBranchShiftMutation();
  const [deleteShift, { isLoading: isDeleting }] = useDeleteBranchShiftMutation();
  const fallbackShifts = branchId && branchId === initialBranchId ? initialShifts : null;
  const shifts = useMemo(
    () => getSettingsCollection(shiftsResponse || fallbackShifts),
    [fallbackShifts, shiftsResponse],
  );
  const branchGender = getGenderForBranchId(branches, branchId, null);

  useEffect(() => {
    setEditor(null);
    setDeleteTarget(null);
    setDeleteConfirmation("");
    setErrors({});
  }, [branchId]);

  /**
   * Updates one shift field and removes its stale validation error.
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
   * Opens an empty shift editor.
   */
  function openCreate() {
    setForm({
      ...createShiftForm(),
      ...(branchGender ? { shiftGender: branchGender } : {}),
    });
    setErrors({});
    setEditor({ mode: "create", shift: null });
  }

  /**
   * Opens the shift editor with the selected record.
   */
  function openEdit(shift) {
    setForm({
      ...createShiftForm(shift),
      ...(branchGender ? { shiftGender: branchGender } : {}),
    });
    setErrors({});
    setEditor({ mode: "edit", shift });
  }

  /**
   * Closes the shift editor and clears validation feedback.
   */
  function closeEditor() {
    setEditor(null);
    setErrors({});
  }

  /**
   * Validates and persists the shift editor form.
   */
  async function saveShift(event) {
    event.preventDefault();
    const validation = shiftSchema.safeParse(form);

    if (!validation.success) {
      setErrors(getFieldErrors(validation.error));
      return;
    }

    try {
      const body = createShiftPayload(form);

      if (editor?.mode === "edit") {
        await updateShift({
          branchId,
          shiftId: editor.shift.id,
          body,
        }).unwrap();
        toast.success("تم تعديل الوردية بنجاح");
      } else {
        await createShift({ branchId, body }).unwrap();
        toast.success("تمت إضافة الوردية بنجاح");
      }

      closeEditor();
    } catch (saveError) {
      toast.error(getApiErrorMessage(saveError, "حدث خطأ أثناء حفظ الوردية."));
    }
  }

  /**
   * Deletes the selected shift after confirmation.
   */
  async function confirmDelete() {
    if (!deleteTarget || deleteConfirmation !== "delete") return;

    try {
      await deleteShift({
        branchId,
        shiftId: deleteTarget.id,
        confirmation: deleteConfirmation,
      }).unwrap();
      toast.success("تم حذف الوردية بنجاح");
      setDeleteTarget(null);
      setDeleteConfirmation("");
    } catch (deleteError) {
      toast.error(getApiErrorMessage(deleteError, "حدث خطأ أثناء حذف الوردية."));
    }
  }

  /**
   * Opens a safe printable document for the selected branch.
   */
  function printShifts() {
    const branch = branches.find((item) => String(item.id) === branchId);
    const didOpen = printBranchShifts({
      branchName: getBranchName(branch),
      shifts,
      formatTime,
      logoUrl: brandLogoUrl,
    });

    if (!didOpen) {
      toast.error("تعذر فتح نافذة الطباعة. يرجى السماح بالنوافذ المنبثقة.");
    }
  }

  return {
    shifts,
    isLoading: isLoading && shifts.length === 0,
    isFetching,
    errorMessage: error ? getApiErrorMessage(error, "تعذر تحميل ورديات الفرع.") : "",
    retry: refetch,
    editor,
    form,
    errors,
    setField,
    openCreate,
    openEdit,
    closeEditor,
    saveShift,
    isSaving: isCreating || isUpdating,
    deleteTarget,
    setDeleteTarget,
    deleteConfirmation,
    setDeleteConfirmation,
    confirmDelete,
    isDeleting,
    printShifts,
  };
}
