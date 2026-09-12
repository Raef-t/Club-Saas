import { cleanup, fireEvent, render, screen, waitFor } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import LockerReserveForm from "./LockerReserveForm";

vi.mock("@/lib/api/branchesApi", () => ({
  useGetBranchSettingsQuery: () => ({
    currentData: { data: { locker_price: 450 } },
    error: null,
    isFetching: false,
  }),
}));

afterEach(cleanup);

describe("LockerReserveForm", () => {
  it("uses the branch locker price and calculates the rental end date", async () => {
    render(
      <LockerReserveForm
        formId="locker-reservation"
        branchId={1}
        members={[]}
        coaches={[]}
        staff={[]}
        onSubmit={vi.fn()}
        onCancel={vi.fn()}
        isLoading={false}
        errorMessage=""
      />,
    );

    fireEvent.click(screen.getByRole("button", { name: "إسناد مجاني" }));
    fireEvent.click(screen.getByRole("option", { name: "إيجار" }));

    const priceInput = screen.getByRole("spinbutton", { name: /السعر من إعدادات الفرع/ });
    expect(priceInput).toHaveValue(450);
    expect(priceInput).toBeDisabled();

    const [startDateInput, endDateInput] = screen.getAllByPlaceholderText("DD/MM/YYYY");
    fireEvent.focus(startDateInput);
    await waitFor(() => expect(screen.getByTitle("اختر الشهر")).toBeInTheDocument());
    fireEvent.change(startDateInput, { target: { value: "31/01/2026" } });
    await waitFor(() => expect(startDateInput).toHaveValue("31/01/2026"));
    fireEvent.blur(startDateInput);

    await waitFor(() => expect(endDateInput).toHaveValue("28/02/2026"));
  });
});
