import { useMemo, useState } from "react";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";
import {
  useGetAppVersionsQuery,
  useCreateAppVersionMutation,
  useUpdateAppVersionMutation,
  useToggleAppVersionStatusMutation,
  useDeleteAppVersionMutation,
} from "@/lib/api/appVersionsApi";
import {
  normalizeVersionList,
  filterAppVersions,
  computeAppVersionStats,
} from "./appVersionsUtils";

export function useAppVersions({ initialVersions } = {}) {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [platformFilter, setPlatformFilter] = useState("all");
  const [statusFilter, setStatusFilter] = useState("all");

  const [createOpen, setCreateOpen] = useState(false);
  const [editTarget, setEditTarget] = useState(null);
  const [deleteTarget, setDeleteTarget] = useState(null);

  const {
    data: versionsResponse,
    isLoading,
    isFetching,
    refetch,
  } = useGetAppVersionsQuery({ per_page: "all" });

  const [createVersion, { isLoading: isCreating }] = useCreateAppVersionMutation();
  const [updateVersion, { isLoading: isUpdating }] = useUpdateAppVersionMutation();
  const [toggleStatus, { isLoading: isToggling }] = useToggleAppVersionStatusMutation();
  const [deleteVersion, { isLoading: isDeleting }] = useDeleteAppVersionMutation();

  const allVersions = useMemo(() => {
    const raw = versionsResponse ?? initialVersions;
    return normalizeVersionList(raw);
  }, [initialVersions, versionsResponse]);

  const filteredVersions = useMemo(() => {
    return filterAppVersions(allVersions, {
      search,
      platform: platformFilter,
      status: statusFilter,
    });
  }, [allVersions, search, platformFilter, statusFilter]);

  const stats = useMemo(() => {
    return computeAppVersionStats(allVersions);
  }, [allVersions]);

  // Handle Create
  async function handleCreate(formData) {
    try {
      await createVersion(formData).unwrap();
      toast.success("تم تسجيل إصدار تطبيق المتدرب بنجاح.");
      setCreateOpen(false);
      refetch();
    } catch (err) {
      toast.error(getApiErrorMessage(err, "فشل في حفظ الإصدار الجديد."));
      throw err;
    }
  }

  // Handle Update
  async function handleUpdate(id, formData) {
    try {
      await updateVersion({ id, body: formData }).unwrap();
      toast.success("تم تحديث إصدار التطبيق بنجاح.");
      setEditTarget(null);
      refetch();
    } catch (err) {
      toast.error(getApiErrorMessage(err, "فشل في تحديث الإصدار."));
      throw err;
    }
  }

  // Handle Toggle Active Status
  async function handleToggleStatus(item) {
    try {
      await toggleStatus(item.id).unwrap();
      toast.success(`تم تغيير حالة الإصدار ${item.version_number}.`);
      refetch();
    } catch (err) {
      toast.error(getApiErrorMessage(err, "تعذر تغيير حالة تفعيل الإصدار."));
    }
  }

  // Handle Delete
  async function handleDeleteConfirm() {
    if (!deleteTarget) return;
    try {
      await deleteVersion(deleteTarget.id).unwrap();
      toast.success("تم حذف إصدار التطبيق بنجاح.");
      setDeleteTarget(null);
      refetch();
    } catch (err) {
      toast.error(getApiErrorMessage(err, "فشل حذف الإصدار."));
    }
  }

  return {
    versions: filteredVersions,
    allVersions,
    stats,
    isLoading: isLoading || isFetching,
    isCreating,
    isUpdating,
    isDeleting,
    isToggling,
    search,
    setSearch,
    platformFilter,
    setPlatformFilter,
    statusFilter,
    setStatusFilter,
    createOpen,
    setCreateOpen,
    editTarget,
    setEditTarget,
    deleteTarget,
    setDeleteTarget,
    handleCreate,
    handleUpdate,
    handleToggleStatus,
    handleDeleteConfirm,
    refetch,
  };
}
