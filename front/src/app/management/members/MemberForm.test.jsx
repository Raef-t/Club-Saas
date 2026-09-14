import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import { MemberForm } from "./MemberForm";

vi.mock("@/lib/ManagementBranchContext", () => ({
  useManagementBranch: () => ({ selectedBranchId: "all" }),
}));

const editValues = {
  first_name: "أحمد",
  last_name: "سمان",
  mobile_country_code: "+963",
  mobile: "",
  gender: "male",
  dob: "",
  age: "",
  branch_id: "1",
  emergency_name: "",
  emergency_relation: "Father",
  emergency_country_code: "+963",
  emergency_phone: "",
  membership_status: "active",
  reason: "",
};

describe("MemberForm", () => {
  it("submits the selected inactive membership status when editing", () => {
    const onSubmit = vi.fn();

    render(
      <MemberForm
        mode="edit"
        initialValues={editValues}
        branches={[{ id: 1, name: "الفرع الرئيسي" }]}
        onSubmit={onSubmit}
        onCancel={vi.fn()}
        isLoading={false}
      />,
    );

    fireEvent.click(screen.getByRole("checkbox", { name: "تغيير حالة المشترك" }));
    fireEvent.change(screen.getByRole("textbox", { name: /سبب التعديل/ }), {
      target: { value: "إيقاف العضوية بطلب المشترك" },
    });
    fireEvent.click(screen.getByRole("button", { name: "حفظ التعديل" }));

    expect(onSubmit).toHaveBeenCalledWith(
      expect.objectContaining({
        membership_status: "inactive",
        reason: "إيقاف العضوية بطلب المشترك",
      }),
    );
  });
});
