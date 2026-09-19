import { fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import { ManagementBranchProvider, useManagementBranch } from "./ManagementBranchContext";

vi.mock("@/lib/api/branchesApi", () => ({
  useGetBranchesQuery: () => ({
    currentData: undefined,
    isLoading: false,
    isFetching: false,
  }),
}));

vi.mock("@/lib/api/clubsApi", () => ({
  useGetClubsQuery: () => ({
    currentData: undefined,
    isFetching: false,
  }),
}));

const branches = [
  { id: 1, name: "الفرع الأول" },
  { id: 2, name: "الفرع الثاني" },
];

function BranchSelectionProbe() {
  const { selectedBranchId, setSelectedBranchId } = useManagementBranch();

  return (
    <button type="button" onClick={() => setSelectedBranchId("2")}>
      {selectedBranchId}
    </button>
  );
}

afterEach(() => {
  vi.restoreAllMocks();
});

describe("ManagementBranchProvider persistence", () => {
  it("shares the selected branch from the application root and removes legacy route cookies", async () => {
    const cookieSetter = vi.spyOn(document, "cookie", "set");

    render(
      <ManagementBranchProvider initialBranches={branches} initialSelectedBranchId="1">
        <BranchSelectionProbe />
      </ManagementBranchProvider>,
    );

    await waitFor(() => {
      expect(cookieSetter).toHaveBeenCalledWith(
        "management_branch_id=1; Path=/; Max-Age=31536000; SameSite=Lax",
      );
    });
    expect(cookieSetter).toHaveBeenCalledWith(
      "management_branch_id=; Path=/management; Max-Age=0; SameSite=Lax",
    );
    expect(cookieSetter).toHaveBeenCalledWith(
      "reports_branch_id=; Path=/reports; Max-Age=0; SameSite=Lax",
    );

    fireEvent.click(screen.getByRole("button", { name: "1" }));

    expect(screen.getByRole("button", { name: "2" })).toBeInTheDocument();
    expect(cookieSetter).toHaveBeenCalledWith(
      "management_branch_id=2; Path=/; Max-Age=31536000; SameSite=Lax",
    );
  });
});
