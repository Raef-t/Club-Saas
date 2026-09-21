import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import StaffForm from "./StaffForm";

afterEach(cleanup);

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({ selectedBranchId: "2" }),
}));

vi.mock("@/lib/api/branchesApi", () => ({
  useGetBranchSettingsQuery: () => ({ data: undefined }),
}));

vi.mock("@/lib/api/usersApi", () => ({
  useGetRolesQuery: () => ({ data: undefined }),
}));

const commonProps = {
  formId: "staff-form",
  onSubmit: vi.fn(),
  onCancel: vi.fn(),
};

describe("staff branch defaults and gender restrictions", () => {
  it("selects the global branch after branches finish loading", async () => {
    const { rerender } = render(<StaffForm {...commonProps} branches={[]} />);

    rerender(
      <StaffForm
        {...commonProps}
        branches={[
          { id: 1, name: "فرع الرجال", gender_restriction: "male" },
          { id: 2, name: "فرع السيدات", gender_restriction: "female" },
        ]}
      />,
    );

    await waitFor(() =>
      expect(screen.getByRole("checkbox", { name: "فرع السيدات" })).toBeChecked(),
    );
    expect(screen.getByText("أنثى")).toBeInTheDocument();
  });

  it("prevents combining male-only and female-only branches", async () => {
    render(
      <StaffForm
        {...commonProps}
        branches={[
          { id: 2, name: "فرع السيدات", gender_restriction: "female" },
          { id: 3, name: "فرع الرجال", gender_restriction: "male" },
        ]}
      />,
    );

    const maleBranch = screen.getByRole("checkbox", { name: "فرع الرجال" });
    fireEvent.click(maleBranch);

    expect(maleBranch).not.toBeChecked();
    expect(
      await screen.findByText("لا يمكن الجمع بين فرع مخصص للذكور وفرع مخصص للإناث."),
    ).toBeInTheDocument();
  });

  it("rejects a gender that conflicts with the selected branch", async () => {
    render(
      <StaffForm
        {...commonProps}
        branches={[{ id: 2, name: "فرع السيدات", gender_restriction: "female" }]}
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: /الجنس/ }));
    fireEvent.click(await screen.findByRole("option", { name: "ذكر" }));

    expect(screen.getByText("أنثى")).toBeInTheDocument();
    expect(await screen.findByText("الفرع المحدد مخصص للإناث فقط.")).toBeInTheDocument();
  });
});
