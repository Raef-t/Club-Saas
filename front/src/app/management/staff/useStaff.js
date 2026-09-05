"use client";

import { useMemo, useState } from "react";
import { useSearchParams } from "next/navigation";
import {
  useCreateStaffMemberMutation,
  useDeleteStaffMemberMutation,
  useGetStaffMemberQuery,
  useGetStaffQuery,
  useUpdateStaffMemberMutation,
} from "@/lib/api/staffApi";
import { useGetBranchesQuery } from "@/lib/api/branchesApi";
import { useManagementBranch } from "@/lib/ManagementBranchContext";
import { useToast } from "@/components/ui/Toast";
import { getApiErrorMessage } from "@/lib/apiError";
import { getBranchesArray } from "@/lib/utils";
import { resolveWorkStatus } from "@/lib/workStatus";
import {
  buildStaffQueryParams,
  createStaffInitialValues,
  getStaffCollection,
  getStaffRecord,
} from "./staffUtils";
import { getPaginationMeta, useServerPagination, withAllItems } from "@/lib/pagination";

export function createStaffFormData(values, { includePhoto = false } = {}) {
  const formData = new FormData();
  formData.append("first_name", values.first_name.trim());
  formData.append("last_name", values.last_name.trim());
  formData.append("phone_number", values.phone_number.trim());
  formData.append("gender", values.gender);
  formData.append("role", values.role);
  formData.append("employment_type", values.employment_type);
  formData.append("base_salary", String(Number(values.base_salary) || 0));
  formData.append("work_status", values.work_status);
  formData.append("is_active", values.work_status === "active" ? "1" : "0");
  formData.append("start_time", values.start_time || "");
  formData.append("end_time", values.end_time || "");

  if (values.country_code) formData.append("country_code", values.country_code.trim());
  if (values.start_date) formData.append("start_date", values.start_date);
  if (values.address) formData.append("address", values.address.trim());
  if (values.reason) formData.append("reason", values.reason.trim());

  values.branch_ids.forEach((id) => formData.append("branch_ids[]", String(id)));

  if (includePhoto && values.photo instanceof File) {
    formData.append("photo", values.photo);
  }

  return formData;
}

export function useStaff({
  selectedStaffId: initialSelectedId,
  fetchDetails = false,
  initialData,
} = {}) {
  const toast = useToast();
  const searchParams = useSearchParams();
  const urlRole = searchParams?.get("role");
  const urlGender = searchParams?.get("gender");
  const urlWorkStatus = searchParams?.get("work_status") || searchParams?.get("status");

  const { selectedBranchId: branchFilter, setSelectedBranchId: setBranchFilter } =
    useManagementBranch();
  const [search, setSearch] = useState("");
  const [roleFilter, setRoleFilter] = useState(urlRole || "all");
  const [genderFilter, setGenderFilter] = useState(urlGender || "all");
  const [workStatusFilter, setWorkStatusFilter] = useState(urlWorkStatus || "all");
  const [drawerMode, setDrawerMode] = useState(null);
  const [selectedStaffId, setSelectedStaffId] = useState(initialSelectedId || null);
  const [formError, setFormError] = useState("");
  const [deleteConfirmOpen, setDeleteConfirmOpen] = useState(false);
  const [itemToDelete, setItemToDelete] = useState(null);
  const [deleteConfirmation, setDeleteConfirmation] = useState("");
  const paginationFilterKey = [
    branchFilter,
    roleFilter,
    genderFilter,
    workStatusFilter,
    search,
  ].join("|");
  const { page, perPage, setPage, setPerPage } = useServerPagination(paginationFilterKey);

  const queryParams = useMemo(
    () => ({
      ...buildStaffQueryParams({
        branchId: branchFilter,
        role: roleFilter,
        gender: genderFilter,
        workStatus: workStatusFilter,
      }),
      page,
      per_page: perPage,
    }),
    [branchFilter, genderFilter, page, perPage, roleFilter, workStatusFilter],
  );

  const {
    currentData: staffResponse,
    error,
    isLoading,
    isFetching,
    refetch,
  } = useGetStaffQuery(queryParams);
  const { currentData: branchesResponse } = useGetBranchesQuery(withAllItems());
  const {
    currentData: detailsResponse,
    error: detailsError,
    isFetching: isFetchingDetails,
    refetch: refetchDetails,
  } = useGetStaffMemberQuery(selectedStaffId, {
    skip: !selectedStaffId || (!fetchDetails && drawerMode !== "details"),
  });
  const [createStaffMember, { isLoading: isCreating }] = useCreateStaffMemberMutation();
  const [updateStaffMember, { isLoading: isUpdating }] = useUpdateStaffMemberMutation();
  const [deleteStaffMember, { isLoading: isDeleting }] = useDeleteStaffMemberMutation();

  const canUseInitialStaff =
    page === 1 &&
    perPage === 15 &&
    branchFilter === "all" &&
    roleFilter === "all" &&
    genderFilter === "all" &&
    workStatusFilter === "all";
  const listResponse = staffResponse || (canUseInitialStaff ? initialData?.staff : null);
  const staff = useMemo(() => getStaffCollection(listResponse), [listResponse]);
  const pagination = useMemo(
    () => getPaginationMeta(listResponse, { page, perPage }),
    [listResponse, page, perPage],
  );
  const branches = useMemo(
    () => getBranchesArray(branchesResponse || initialData?.branches),
    [branchesResponse, initialData?.branches],
  );
  const detailsStaff = useMemo(() => getStaffRecord(detailsResponse), [detailsResponse]);
  const selectedStaff = useMemo(
    () => detailsStaff || staff.find((item) => Number(item.id) === Number(selectedStaffId)) || null,
    [detailsStaff, selectedStaffId, staff],
  );

  const filteredStaff = useMemo(() => {
    const term = search.trim().toLowerCase();
    if (!term) return staff;

    return staff.filter((item) => {
      const values = [
        item.person?.full_name,
        item.person?.phone_number,
        item.person?.address,
        item.username,
        item.role,
      ];
      return values.filter(Boolean).some((value) => String(value).toLowerCase().includes(term));
    });
  }, [search, staff]);
  const totalResults = search.trim() ? filteredStaff.length : pagination.total;

  const stats = useMemo(() => {
    const activeCount = staff.filter((item) => resolveWorkStatus(item) === "active").length;
    const managementCount = staff.filter((item) =>
      ["admin", "management_admin", "manager"].includes(item.role),
    ).length;
    const fixedSalaryCount = staff.filter((item) => item.employment_type === "fixed_salary").length;

    return [
      {
        title: "إجمالي الموظفين",
        value: staff.length.toLocaleString("ar"),
        helper: "الموظفون المسجلون في النظام",
        tone: "yellow",
        iconKey: "members",
        compact: true,
        onClick: () => {
          setRoleFilter("all");
          setWorkStatusFilter("all");
          setGenderFilter("all");
        },
        active: roleFilter === "all" && workStatusFilter === "all" && genderFilter === "all",
      },
      {
        title: "الموظفون النشطون",
        value: activeCount.toLocaleString("ar"),
        helper: "الموظفون المتاحون حاليًا",
        tone: "green",
        iconKey: "members",
        compact: true,
        onClick: () => setWorkStatusFilter(workStatusFilter === "active" ? "all" : "active"),
        active: workStatusFilter === "active",
      },
      {
        title: "الإدارة",
        value: managementCount.toLocaleString("ar"),
        helper: "المديرون ومسؤولو النظام",
        tone: "blue",
        compact: true,
        onClick: () =>
          setRoleFilter(
            roleFilter === "manager" || roleFilter === "admin" ? "all" : "management_admin",
          ),
        active:
          roleFilter === "admin" || roleFilter === "management_admin" || roleFilter === "manager",
      },
      {
        title: "رواتب ثابتة",
        value: fixedSalaryCount.toLocaleString("ar"),
        helper: "موظفون براتب شهري ثابت",
        tone: "purple",
        compact: true,
      },
    ];
  }, [genderFilter, roleFilter, staff, workStatusFilter]);

  function closeDrawer() {
    setDrawerMode(null);
    setSelectedStaffId(null);
    setFormError("");
  }

  async function handleCreate(values) {
    setFormError("");
    try {
      const response = await createStaffMember(
        createStaffFormData(values, { includePhoto: true }),
      ).unwrap();
      toast.success(response?.message || "تمت إضافة الموظف بنجاح.");
      return response;
    } catch (submitError) {
      setFormError(
        getApiErrorMessage(submitError, "تعذر إضافة الموظف. تحقق من البيانات وحاول مرة أخرى."),
      );
      return false;
    }
  }

  async function handleUpdate(values) {
    if (!selectedStaffId) return false;
    setFormError("");
    try {
      const response = await updateStaffMember({
        id: selectedStaffId,
        body: createStaffFormData(values),
      }).unwrap();
      toast.success(response?.message || "تم تعديل بيانات الموظف بنجاح.");
      return response;
    } catch (submitError) {
      setFormError(
        getApiErrorMessage(
          submitError,
          "تعذر تعديل بيانات الموظف. تحقق من البيانات وحاول مرة أخرى.",
        ),
      );
      return false;
    }
  }

  function getEditInitialValues() {
    if (!selectedStaff || (fetchDetails && !detailsResponse)) return null;
    return createStaffInitialValues({ staff: selectedStaff, branches });
  }

  function handleDelete(item) {
    setItemToDelete(item);
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
      const response = await deleteStaffMember({
        id: itemToDelete.id,
        confirmation: deleteConfirmation,
      }).unwrap();
      toast.success(response?.message || "تم حذف الموظف بنجاح.");
      if (Number(selectedStaffId) === Number(itemToDelete.id)) closeDrawer();
      closeDeleteConfirm();
    } catch (deleteError) {
      toast.error(getApiErrorMessage(deleteError, "تعذر حذف الموظف. حاول مرة أخرى."));
    }
  }

  return {
    search,
    setSearch,
    branchFilter,
    setBranchFilter,
    roleFilter,
    setRoleFilter,
    genderFilter,
    setGenderFilter,
    workStatusFilter,
    setWorkStatusFilter,
    drawerMode,
    setDrawerMode,
    selectedStaffId,
    setSelectedStaffId,
    formError,
    setFormError,
    isLoading: isLoading || (isFetching && !listResponse),
    error,
    refetch,
    filteredStaff,
    pagination: { ...pagination, setPage, setPerPage },
    totalResults,
    stats,
    selectedStaff,
    detailsStaff,
    detailsError,
    isFetchingDetails,
    refetchDetails,
    isCreating,
    isUpdating,
    isDeleting,
    handleCreate,
    handleUpdate,
    handleDelete,
    confirmDelete,
    closeDeleteConfirm,
    deleteConfirmOpen,
    itemToDelete,
    deleteConfirmation,
    setDeleteConfirmation,
    getEditInitialValues,
    branches,
    closeDrawer,
  };
}
