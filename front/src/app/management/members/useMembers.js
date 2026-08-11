import { useMemo, useState } from "react";
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
  const { selectedBranchId: branchFilter, setSelectedBranchId: setBranchFilter } =
    useManagementBranch();
  const [search, setSearch] = useState("");
  const [genderFilter, setGenderFilter] = useState("all");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedMemberId, setSelectedMemberId] = useState(initialSelectedMemberId);
  const [formError, setFormError] = useState("");
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);

  const queryParams = useMemo(() => {
    return branchFilter !== "all" ? { branch_id: branchFilter } : {};
  }, [branchFilter]);

  const { data, error, isLoading, refetch } = useGetMembersQuery(queryParams);
  const { data: branchesData } = useGetBranchesQuery();
  const { data: plansData } = useGetSubscriptionPlansQuery(queryParams);

  const [createPlayer, { isLoading: isCreating }] = useCreatePlayerMutation();
  const [updatePlayer, { isLoading: isUpdating }] = useUpdatePlayerMutation();
  const [deleteMember, { isLoading: isDeleting }] = useDeleteMemberMutation();
  const [createPlayerSubscription] = useCreatePlayerSubscriptionMutation();

  const members = useMemo(
    () => getMembersArray(data || initialData?.members),
    [data, initialData?.members],
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

      return matchesGender && matchesSearch;
    });
  }, [branchMembers, genderFilter, search]);

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
      },
      {
        title: "الأعضاء النشطين",
        value: activeCount.toLocaleString("ar"),
        helper: "اللاعبين ذوي الاشتراكات الفعالة",
        tone: "green",
        compact: true,
      },
      {
        title: "الذكور",
        value: maleCount.toLocaleString("ar"),
        helper: "اللاعبين الرجال",
        tone: "blue",
        compact: true,
      },
      {
        title: "الإناث",
        value: femaleCount.toLocaleString("ar"),
        helper: "اللاعبات السيدات",
        tone: "purple",
        compact: true,
      },
    ];
  }, [branchMembers]);

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
    setDeleteConfirmOpen(true);
  }

  function closeDeleteConfirm() {
    setDeleteConfirmOpen(false);
    setItemToDelete(null);
  }

  async function confirmDelete() {
    if (!itemToDelete) return;
    try {
      await deleteMember(itemToDelete.id).unwrap();
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
    };
  }

  return {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    genderFilter,
    setGenderFilter,
    drawerMode,
    setDrawerMode,
    selectedMemberId,
    setSelectedMemberId,
    formError,
    setFormError,
    isLoading,
    error,
    refetch,
    filteredMembers,
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
    getEditInitialValues,
    branches,
    plans,
    closeDrawer,
  };
}
