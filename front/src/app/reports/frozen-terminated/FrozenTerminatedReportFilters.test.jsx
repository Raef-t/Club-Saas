import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import FrozenTerminatedReportFilters from "./FrozenTerminatedReportFilters";
import { DEFAULT_FROZEN_TERMINATED_FILTERS } from "./frozenTerminatedReportUtils";

describe("FrozenTerminatedReportFilters", () => {
  afterEach(cleanup);

  it("renders with dropdowns and handles status selection", () => {
    const onChange = vi.fn();
    const { container } = render(
      <FrozenTerminatedReportFilters
        filters={DEFAULT_FROZEN_TERMINATED_FILTERS}
        plans={[{ value: "1", label: "أجهزة عام" }]}
        validationError=""
        isFetching={false}
        onChange={onChange}
        onApply={() => true}
        onReset={() => {}}
      />
    );

    expect(container.querySelector("select")).not.toBeInTheDocument();

    fireEvent.click(screen.getByRole("button", { name: "حالة الاشتراك" }));
    fireEvent.click(screen.getByRole("option", { name: "المجمدة فقط (❄️)" }));

    expect(onChange).toHaveBeenCalledWith("status", "frozen");
  });

  it("toggles the filter card accordion", () => {
    render(
      <FrozenTerminatedReportFilters
        filters={DEFAULT_FROZEN_TERMINATED_FILTERS}
        plans={[]}
        validationError=""
        isFetching={false}
        onChange={() => {}}
        onApply={() => true}
        onReset={() => {}}
      />
    );

    const toggleButton = screen.getByRole("button", { name: /فلاتر التقرير/ });
    expect(toggleButton).toHaveAttribute("aria-expanded", "true");
    expect(screen.getByRole("button", { name: "حالة الاشتراك" })).toBeInTheDocument();

    fireEvent.click(toggleButton);

    expect(toggleButton).toHaveAttribute("aria-expanded", "false");
    expect(screen.queryByRole("button", { name: "حالة الاشتراك" })).not.toBeInTheDocument();
  });
});
