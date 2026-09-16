import { cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import MembersClient from "./MembersClient";

vi.mock("next/navigation", () => ({
  useRouter: () => ({ push: vi.fn() }),
}));

vi.mock("@/lib/PermissionContext", () => ({
  usePermissions: () => ({ can: () => true }),
}));

vi.mock("./useMembers", () => ({
  useMembers: () => ({
    search: "",
    setSearch: vi.fn(),
    branchFilter: "all",
    setBranchFilter: vi.fn(),
    genderFilter: "all",
    setGenderFilter: vi.fn(),
    drawerMode: null,
    setDrawerMode: vi.fn(),
    selectedMemberId: null,
    setSelectedMemberId: vi.fn(),
    formError: "",
    setFormError: vi.fn(),
    isLoading: false,
    error: null,
    refetch: vi.fn(),
    filteredMembers: [
      {
        id: 7,
        generated_username: "tec-ply-10007",
        custom_username: "dania.player",
        membership_status: "active",
        created_by: { username: "reception.ahmad", name: "أحمد محمد" },
        person: {
          full_name: "دانية مولوي",
          gender: "female",
          dob: "2000-01-01",
        },
      },
    ],
    pagination: {
      currentPage: 1,
      lastPage: 1,
      total: 1,
      perPage: 15,
      setPage: vi.fn(),
      setPerPage: vi.fn(),
    },
    totalResults: 1,
    stats: [],
    selectedMember: null,
    isCreating: false,
    isUpdating: false,
    isDeleting: false,
    handleCreate: vi.fn(),
    handleUpdate: vi.fn(),
    handleDelete: vi.fn(),
    closeDeleteConfirm: vi.fn(),
    confirmDelete: vi.fn(),
    deleteConfirmOpen: false,
    itemToDelete: null,
    deleteConfirmation: "",
    setDeleteConfirmation: vi.fn(),
    getEditInitialValues: () => null,
    branches: [],
    plans: [],
    closeDrawer: vi.fn(),
  }),
}));

describe("members table", () => {
  afterEach(() => cleanup());

  it("numbers rows and shows the registering username instead of date of birth", () => {
    render(<MembersClient initialData={{}} />);

    expect(screen.getAllByText("#").length).toBeGreaterThan(0);
    expect(screen.getAllByText("المستخدم المسؤول").length).toBeGreaterThan(0);
    expect(screen.queryByText("تاريخ الميلاد")).not.toBeInTheDocument();
    expect(screen.getAllByText("reception.ahmad").length).toBeGreaterThan(0);
    expect(screen.getAllByText("dania.player").length).toBeGreaterThan(0);
    expect(screen.getAllByText("1").length).toBeGreaterThan(0);
  });
});
