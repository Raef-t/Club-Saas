import { useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
import {
  useGetMembersQuery,
  useCreatePlayerMutation,
  useUpdatePlayerMutation,
  useDeleteMemberMutation,
} from "@/lib/api/membersApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useGetSubscriptionPlansQuery } from "@/lib/api/subscriptionPlansApi";
import { useCreatePlayerSubscriptionMutation } from "@/lib/api/playerSubscriptionsApi";
import { useToast } from "@/components/ui/Toast";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { filterEntitiesByBranch } from "@/lib/managementBranchUtils";
import { getPaginationMeta, useServerPagination, withAllItems } from "@/lib/pagination";

function getMembersArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

function getBranchesArray(response) {
  if (Array.isArray(response)) return response;
  if (Array.isArray(response?.data)) return response.data;
  if (Array.isArray(response?.data?.data)) return response.data.data;
  return [];
}

function getPlansArray(response) {
  return Array.isArray(response?.data) ? response.data : [];
}

/**
 * Coordinates member data, filters, selection, and CRUD mutations.
 */
export function useMembers({ selectedMemberId: initialSelectedMemberId = null, initialData } = {}) {
  const toast = useToast();
  const searchParams = useSearchParams();
  const urlStatus = searchParams?.get("status");
  const urlGender = searchParams?.get("gender");

  const { selectedBranchId: branchFilter, setSelectedBranchId: setBranchFilter } =
    useManagementBranch();
  const [search, setSearch] = useState("");
  const [genderFilter, setGenderFilter] = useState(urlGender || "all");
  const [statusFilter, setStatusFilter] = useState(urlStatus || "all");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedMemberId, setSelectedMemberId] = useState(initialSelectedMemberId);
  const [formError, setFormError] = useState("");
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const paginationFilterKey = [branchFilter, genderFilter, statusFilter, search].join("|");
  const { page, perPage, setPage, setPerPage } = useServerPagination(paginationFilterKey);

  const queryParams = useMemo(() => {
    return {
      ...(branchFilter !== "all" ? { branch_id: branchFilter } : {}),
      ...(genderFilter !== "all" ? { gender: genderFilter } : {}),
      ...(statusFilter !== "all" ? { status: statusFilter } : {}),
      page,
      per_page: perPage,
    };
  }, [branchFilter, genderFilter, page, perPage, statusFilter]);

  const { currentData: data, error, isLoading, isFetching, refetch } =
    useGetMembersQuery(queryParams);
  const { data: branchesData } = useGetBranchesQuery(withAllItems());
  const { data: plansData } = useGetSubscriptionPlansQuery(
    withAllItems(branchFilter !== "all" ? { branch_id: branchFilter } : {}),
  );

  const [createPlayer, { isLoading: isCreating }] = useCreatePlayerMutation();
  const [updatePlayer, { isLoading: isUpdating }] = useUpdatePlayerMutation();
  const [deleteMember, { isLoading: isDeleting }] = useDeleteMemberMutation();
  const [createPlayerSubscription] = useCreatePlayerSubscriptionMutation();

  const canUseInitialMembers =
    page === 1 &&
    perPage === 15 &&
    branchFilter === "all" &&
    genderFilter === "all" &&
    statusFilter === "all";
  const membersResponse = data || (canUseInitialMembers ? initialData?.members : null);
  const members = useMemo(() => getMembersArray(membersResponse), [membersResponse]);
  const pagination = useMemo(
    () => getPaginationMeta(membersResponse, { page, perPage }),
    [membersResponse, page, perPage],
  );
  const branches = useMemo(
    () => getBranchesArray(branchesData || initialData?.branches),
    [branchesData, initialData?.branches],
  );
  const allPlans = useMemo(
    () => getPlansArray(plansData || initialData?.plans),
    [plansData, initialData?.plans],
  );
  const plans = useMemo(
    () => filterEntitiesByBranch(allPlans, branchFilter),
    [allPlans, branchFilter],
  );

  const selectedMember = useMemo(
    () => members.find((m) => m.id === selectedMemberId) || null,
    [members, selectedMemberId],
  );

  const branchMembers = useMemo(
    () => filterEntitiesByBranch(members, branchFilter),
    [branchFilter, members],
  );
  const filteredMembers = useMemo(() => {
    const normalizedSearch = search.trim().toLowerCase();

    return branchMembers.filter((m) => {
      const person = m.person || {};
      const fullName = (person.full_name || `${m.first_name || ""} ${m.last_name || ""}`)
        .trim()
        .toLowerCase();
      const mobileVal = person.phone || person.mobile || m.mobile || "";

      const matchesGender = genderFilter === "all" || (person.gender || m.gender) === genderFilter;
      const matchesStatus =
        statusFilter === "all" ||
        (statusFilter === "active" ? m.is_active !== false : m.is_active === false);

      const matchesSearch =
        !normalizedSearch ||
        fullName.includes(normalizedSearch) ||
        String(mobileVal).includes(normalizedSearch) ||
        String(m.qr_code || "")
          .toLowerCase()
          .includes(normalizedSearch) ||
        String(m.generated_username || m.username || "")
          .toLowerCase()
          .includes(normalizedSearch);

      return matchesGender && matchesStatus && matchesSearch;
    });
  }, [branchMembers, genderFilter, search, statusFilter]);
  const totalResults = search.trim() ? filteredMembers.length : pagination.total;

  const stats = useMemo(() => {
    const activeCount = branchMembers.filter((m) => m.is_active !== false).length;
    const maleCount = branchMembers.filter((m) => (m.person?.gender || m.gender) === "male").length;
    const femaleCount = branchMembers.filter(
      (m) => (m.person?.gender || m.gender) === "female",
    ).length;

    return [
      {
        title: "إجمالي الأعضاء",
        value: branchMembers.length.toLocaleString("ar"),
        helper: "كل اللاعبين المسجلين",
        tone: "yellow",
        compact: true,
        onClick: () => {
          setStatusFilter("all");
          setGenderFilter("all");
        },
        active: statusFilter === "all" && genderFilter === "all",
      },
      {
        title: "الأعضاء النشطين",
        value: activeCount.toLocaleString("ar"),
        helper: "اللاعبين ذوي الاشتراكات الفعالة",
        tone: "green",
        compact: true,
        onClick: () => setStatusFilter(statusFilter === "active" ? "all" : "active"),
        active: statusFilter === "active",
      },
      {
        title: "الذكور",
        value: maleCount.toLocaleString("ar"),
        helper: "اللاعبين الرجال",
        tone: "blue",
        compact: true,
        onClick: () => setGenderFilter(genderFilter === "male" ? "all" : "male"),
        active: genderFilter === "male",
      },
      {
        title: "الإناث",
        value: femaleCount.toLocaleString("ar"),
        helper: "اللاعبات السيدات",
        tone: "purple",
        compact: true,
        onClick: () => setGenderFilter(genderFilter === "female" ? "all" : "female"),
        active: genderFilter === "female",
      },
    ];
  }, [branchMembers, genderFilter, statusFilter]);

  function closeDrawer() {
    setDrawerMode(null);
    setSelectedMemberId(null);
    setFormError("");
  }

  async function handleCreate(values) {
    setFormError("");
    try {
      const { plans: _plansPayload, ...memberDetails } = values;

      const memberResult = await createPlayer(memberDetails).unwrap();

      const memberId = memberResult?.data?.member?.id || memberResult?.data?.id || memberResult?.id;
      if (!memberId) {
        throw new Error("لم يتم الحصول على معرف العضو الجديد");
      }

      toast.success("تم تسجيل اللاعب العضو بنجاح!");
      closeDrawer();
      return memberResult;
    } catch (submitError) {
      console.error("Create member error:", submitError);
      const rawMsg = submitError?.data?.message || "";
      if (rawMsg.includes("endpoint or resource was not found") || submitError?.status === 404) {
        setFormError("عذراً، الرابط البرمجي لإضافة اللاعب غير متوفر حالياً على الخادم (404).");
      } else {
        setFormError(rawMsg || "تعذر إضافة العضو. تحقق من البيانات وحاول مرة أخرى.");
      }
      return false;
    }
  }

  async function handleUpdate(values) {
    if (!selectedMemberId) return false;
    setFormError("");
    try {
      await updatePlayer({ id: selectedMemberId, body: values }).unwrap();
      toast.success("تم تعديل بيانات اللاعب بنجاح!");
      closeDrawer();
      return true;
    } catch (submitError) {
      console.error("Update member error:", submitError);
      const rawMsg = submitError?.data?.message || "";
      if (rawMsg.includes("endpoint or resource was not found") || submitError?.status === 404) {
        setFormError(
          "عذراً، الرابط البرمجي لتعديل بيانات اللاعب غير متوفر حالياً على الخادم (404).",
        );
      } else {
        setFormError(rawMsg || "تعذر تعديل بيانات العضو. تحقق من البيانات وحاول مرة أخرى.");
      }
      return false;
    }
  }

  function handleDelete(member) {
    setItemToDelete(member);
    setDeleteConfirmation("");
    setDeleteConfirmOpen(true);
  }

  function closeDeleteConfirm() {
    setDeleteConfirmOpen(false);
    setItemToDelete(null);
    setDeleteConfirmation("");
  }

  async function confirmDelete() {
    if (!itemToDelete || deleteConfirmation !== "delete") return;
    try {
      await deleteMember({ id: itemToDelete.id, confirmation: deleteConfirmation }).unwrap();
      toast.success("تم حذف اللاعب العضو بنجاح!");
    } catch {
      toast.error("تعذر حذف اللاعب العضو. حاول مرة أخرى.");
    } finally {
      closeDeleteConfirm();
    }
  }

  function getEditInitialValues() {
    if (!selectedMember) return null;

    const person = selectedMember.person || {};
    const fullName = person.full_name || "";
    const nameParts = fullName.trim().split(/\s+/);
    const firstName = person.first_name || selectedMember.first_name || nameParts[0] || "";
    const lastName =
      person.last_name || selectedMember.last_name || nameParts.slice(1).join(" ") || "";
    const gender = person.gender || selectedMember.gender || "male";
    const dob = person.dob || selectedMember.dob || "";
    const mobile = person.phone || person.mobile || selectedMember.mobile || "";
    const mobileCountryCode =
      person.mobile_country_code || selectedMember.mobile_country_code || "+963";
    const age = person.age || selectedMember.age || "";

    const contact = selectedMember.additional_contacts?.[0] || null;

    return {
      first_name: firstName,
      last_name: lastName,
      mobile_country_code: mobileCountryCode,
      mobile: mobile,
      gender: gender,
      dob: dob ? dob.split("T")[0] : "",
      age: age,
      branch_id: String(selectedMember.branch_id || ""),
      emergency_name: contact?.name || "",
      emergency_relation: contact?.relation || "Father",
      emergency_country_code: contact?.country_code || "+963",
      emergency_phone: contact?.phone_number || "",
      reason: "",
    };
  }

  return {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    genderFilter,
    setGenderFilter,
    statusFilter,
    setStatusFilter,
    drawerMode,
    setDrawerMode,
    selectedMemberId,
    setSelectedMemberId,
    formError,
    setFormError,
    isLoading: isLoading || (isFetching && !membersResponse),
    error,
    refetch,
    filteredMembers,
    pagination: { ...pagination, setPage, setPerPage },
    totalResults,
    stats,
    selectedMember,
    isCreating,
    isUpdating,
    isDeleting,
    handleCreate,
    handleUpdate,
    handleDelete,
    closeDeleteConfirm,
    confirmDelete,
    deleteConfirmOpen,
    itemToDelete,
    deleteConfirmation,
    setDeleteConfirmation,
    getEditInitialValues,
    branches,
    plans,
    closeDrawer,
  };
}
