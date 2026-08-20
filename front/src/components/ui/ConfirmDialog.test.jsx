import { useState } from "react";
import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import ConfirmDialog from "./ConfirmDialog";

function TypedConfirmationDialog({ onConfirm }) {
  const [value, setValue] = useState("");

  return (
    <ConfirmDialog
      open
      onClose={() => {}}
      onConfirm={onConfirm}
      requiredConfirmation="delete"
      confirmationValue={value}
      onConfirmationChange={setValue}
      confirmationLabel="اكتب كلمة delete لتأكيد الحذف"
    />
  );
}

describe("ConfirmDialog typed confirmation", () => {
  it("enables deletion only after typing delete exactly", () => {
    const onConfirm = vi.fn();
    render(<TypedConfirmationDialog onConfirm={onConfirm} />);

    const confirmButton = screen.getByRole("button", { name: "حذف" });
    const confirmationInput = screen.getByPlaceholderText("delete");

    expect(confirmButton).toBeDisabled();

    fireEvent.change(confirmationInput, { target: { value: "Delete" } });
    expect(confirmButton).toBeDisabled();

    fireEvent.change(confirmationInput, { target: { value: "delete" } });
    expect(confirmButton).toBeEnabled();

    fireEvent.click(confirmButton);
    expect(onConfirm).toHaveBeenCalledOnce();
  });

  it("renders custom children such as checkboxes and inputs", () => {
    render(
      <ConfirmDialog open onClose={() => {}} onConfirm={() => {}}>
        <div data-testid="custom-checkbox-container">
          <input type="checkbox" data-testid="refund-checkbox" />
          <label htmlFor="refund-checkbox">إعادة سعر الاشتراك</label>
        </div>
      </ConfirmDialog>,
    );

    expect(screen.getByTestId("custom-checkbox-container")).toBeInTheDocument();
    expect(screen.getByTestId("refund-checkbox")).toBeInTheDocument();
    expect(screen.getByText("إعادة سعر الاشتراك")).toBeInTheDocument();
  });
});
