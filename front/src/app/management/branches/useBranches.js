import { useMemo, useState } from "react";
import {
  useGetBranchesQuery,
  useGetBranchQuery,
  useCreateBranchMutation,
  useUpdateBranchMutation,
  useDeleteBranchMutation,
  useToggleBranchStatusMutation,
} from "@/lib/api/branchesApi";
import { useGetClubsQuery } from "@/lib/api/clubsApi";
import { useToast } from "@/components/ui/Toast";

function getBranchesArray(response) {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  return [];
}

function getClubsArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function branchName(branch) {
  if (!branch?.name) return "-";
  if (typeof branch.name === "string") return branch.name;
  return branch.name.ar || branch.name.en || "-";
}

export function useBranches({ selectedBranchId: initialSelectedBranchId = null } = {}) {
  const toast = useToast();
  const [search, setSearch] = useState("");
  const [genderFilter, setGenderFilter] = useState("all");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedBranchId, setSelectedBranchId] = useState(initialSelectedBranchId);
  const [formError, setFormError] = useState("");
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);

  const { data, error, isLoading, refetch } = useGetBranchesQuery();
  const { data: clubsData } = useGetClubsQuery();

  const {
    data: detailsData,
    error: detailsError,
    isFetching: isFetchingDetails,
  } = useGetBranchQuery(selectedBranchId, {
    skip: !selectedBranchId || drawerMode !== "details",
  });

  const [createBranch, { isLoading: isCreating }] = useCreateBranchMutation();
  const [updateBranch, { isLoading: isUpdating }] = useUpdateBranchMutation();
  const [deleteBranch, { isLoading: isDeleting }] = useDeleteBranchMutation();
  const [toggleStatus, { isLoading: isToggling }] =
    useToggleBranchStatusMutation();

  const branches = useMemo(() => getBranchesArray(data), [data]);
  const clubs = useMemo(() => getClubsArray(clubsData), [clubsData]);

  const selectedBranch = useMemo(
    () => branches.find((b) => b.id === selectedBranchId) || null,
    [branches, selectedBranchId],
  );

  const detailsBranch = useMemo(() => detailsData?.data || null, [detailsData]);

  const filteredBranches = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();

    return branches.filter((branch) => {
      const nameVal =
        typeof branch.name === "string"
          ? branch.name
          : branch.name?.ar || branch.name?.en || "";
      const matchesGender =
        genderFilter === "all" || branch.gender_restriction === genderFilter;
      const matchesSearch =
        !normalizedSearch ||
        [nameVal, branch.address, branch.phone]
          .filter(Boolean)
          .some((value) =>
            String(value).toLowerCase().includes(normalizedSearch),
          );

      return matchesGender && matchesSearch;
    });
  }, [branches, search, genderFilter]);

  const stats = useMemo(() => {
    const activeCount = branches.filter((b) => b.is_active).length;
    const maleCount = branches.filter((b) => b.gender_restriction === "male").length;
    const femaleCount = branches.filter((b) => b.gender_restriction === "female").length;

    return [
      {
        title: "إجمالي الفروع",
        value: branches.length.toLocaleString("ar"),
        helper: "كل الفروع المسجلة",
        tone: "yellow",
        compact: true,
      },
      {
        title: "الفروع النشطة",
        value: activeCount.toLocaleString("ar"),
        helper: "الفروع التي تعمل حالياً",
        tone: "green",
        compact: true,
      },
      {
        title: "فروع الرجال",
        value: maleCount.toLocaleString("ar"),
        helper: "مخصصة للذكور فقط",
        tone: "blue",
        compact: true,
      },
      {
        title: "فروع السيدات",
        value: femaleCount.toLocaleString("ar"),
        helper: "مخصصة للإناث فقط",
        tone: "purple",
        compact: true,
      },
    ];
  }, [branches]);

  function closeDrawer() {
    setDrawerMode(null);
    setSelectedBranchId(null);
    setFormError("");
  }

  async function handleCreate(values) {
    setFormError("");
    try {
      await createBranch(values).unwrap();
      toast.success("تم إنشاء الفرع بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      console.error("DEBUG handleCreate Error:", submitError);
      let errorMsg = "تعذر إنشاء الفرع. تحقق من البيانات وحاول مرة أخرى.";
      if (submitError?.data?.errors) {
        const errors = submitError.data.errors;
        const messages = Object.keys(errors).map(key => {
          return `${key}: ${errors[key].join(", ")}`;
        });
        errorMsg = messages.join(" | ");
      } else if (submitError?.data?.message) {
        errorMsg = submitError.data.message;
      }
      setFormError(errorMsg);
      return false;
    }
  }

  async function handleUpdate(values) {
    if (!selectedBranchId) return false;
    setFormError("");
    try {
      await updateBranch({ id: selectedBranchId, body: values }).unwrap();
      toast.success("تم تعديل بيانات الفرع بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      console.error("DEBUG handleUpdate Error:", submitError);
      let errorMsg = "تعذر تعديل الفرع. تحقق من البيانات وحاول مرة أخرى.";
      if (submitError?.data?.errors) {
        const errors = submitError.data.errors;
        const messages = Object.keys(errors).map(key => {
          return `${key}: ${errors[key].join(", ")}`;
        });
        errorMsg = messages.join(" | ");
      } else if (submitError?.data?.message) {
        errorMsg = submitError.data.message;
      }
      setFormError(errorMsg);
      return false;
    }
  }

  function handleDelete(branch) {
    setItemToDelete(branch);
    setDeleteConfirmOpen(true);
  }

  function closeDeleteConfirm() {
    setDeleteConfirmOpen(false);
    setItemToDelete(null);
  }

  async function confirmDelete() {
    if (!itemToDelete) return;
    try {
      await deleteBranch(itemToDelete.id).unwrap();
      toast.success("تم حذف الفرع بنجاح!");
    } catch {
      toast.error("تعذر حذف الفرع. حاول مرة أخرى.");
    } finally {
      closeDeleteConfirm();
    }
  }

  async function handleToggleStatus(branch) {
    try {
      await toggleStatus(branch.id).unwrap();
      toast.success("تم تغيير حالة الفرع بنجاح!");
    } catch {
      toast.error("تعذر تغيير حالة الفرع. حاول مرة أخرى.");
    }
  }

  function getEditInitialValues() {
    if (!selectedBranch) return null;

    const nameAr =
      typeof selectedBranch.name === "object"
        ? selectedBranch.name?.ar
        : selectedBranch.name;
    const nameEn =
      typeof selectedBranch.name === "object" ? selectedBranch.name?.en : "";

    return {
      club_id: String(selectedBranch.club_id || clubs[0]?.id || 1),
      name_ar: nameAr || "",
      name_en: nameEn || "",
      gender_restriction: selectedBranch.gender_restriction || "mixed",
      address: selectedBranch.address || "",
      country_code: selectedBranch.country_code || "+963",
      phone: selectedBranch.phone || "",
      type: selectedBranch.type || "gym",
    };
  }

  return {
    search,
    setSearch,
    genderFilter,
    setGenderFilter,
    drawerMode,
    setDrawerMode,
    selectedBranchId,
    setSelectedBranchId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredBranches,
    stats,
    selectedBranch,
    detailsBranch,
    isFetchingDetails,
    detailsError,
    isCreating,
    isUpdating,
    isDeleting,
    isToggling,
    handleCreate,
    handleUpdate,
    handleDelete,
    confirmDelete,
    closeDeleteConfirm,
    deleteConfirmOpen,
    itemToDelete,
    handleToggleStatus,
    getEditInitialValues,
    clubs,
    closeDrawer,
  };
}
