import { renderHook } from "@testing-library/react";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { useUsers } from "./useUsers";

const { mockUseGetUsersQuery } = vi.hoisted(() => ({
  mockUseGetUsersQuery: vi.fn(),
}));

vi.mock("next/navigation", () => ({
  useSearchParams: () => new URLSearchParams(),
}));

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({ selectedBranchId: "7" }),
}));

vi.mock("@/lib/api/usersApi", () => ({
  useGetUsersQuery: mockUseGetUsersQuery,
}));

const branchUser = {
  id: 7,
  name: "مستخدم الفرع",
  username: "branch-user",
  roles: ["coach"],
  branch_id: 7,
};

function createQueryResult(currentData) {
  return {
    currentData,
    error: null,
    isLoading: false,
    isFetching: false,
    refetch: vi.fn(),
  };
}

describe("useUsers branch filtering", () => {
  beforeEach(() => {
    mockUseGetUsersQuery.mockReset();
    mockUseGetUsersQuery.mockImplementation(() =>
      createQueryResult({ data: [branchUser] }),
    );
  });

  it("scopes the summary and paginated list queries to the selected branch", () => {
    const { result } = renderHook(() =>
      useUsers({
        initialUsers: {
          data: [
            branchUser,
            { id: 8, name: "مستخدم فرع آخر", roles: ["player"], branch_id: 8 },
          ],
        },
      }),
    );

    expect(mockUseGetUsersQuery).toHaveBeenNthCalledWith(
      1,
      expect.objectContaining({ branch_id: "7", per_page: "all" }),
    );
    expect(mockUseGetUsersQuery).toHaveBeenNthCalledWith(
      2,
      expect.objectContaining({ branch_id: "7", page: 1, per_page: 15 }),
    );
    expect(result.current.users).toEqual([branchUser]);
    expect(result.current.stats[0].value).toBe((1).toLocaleString("ar"));
    expect(result.current.roleOptions).toEqual([
      { value: "all", label: "الكل" },
      { value: "coach", label: "المدربون" },
    ]);
  });
});
