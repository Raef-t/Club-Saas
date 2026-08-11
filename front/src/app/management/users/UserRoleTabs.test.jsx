import { cleanup, fireEvent, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it, vi } from "vitest";
import UserRoleTabs from "./UserRoleTabs";

afterEach(cleanup);

const items = [
  { value: "all", label: "الكل" },
  { value: "manager", label: "المدراء" },
  { value: "accountant", label: "المحاسبون" },
];

describe("UserRoleTabs", () => {
  it("shows account categories and selects a clicked tab", () => {
    const onChange = vi.fn();
    render(<UserRoleTabs items={items} value="all" onChange={onChange} />);

    expect(screen.getByRole("tab", { name: /الكل/ })).toHaveAttribute("aria-selected", "true");
    fireEvent.click(screen.getByRole("tab", { name: /المدراء/ }));

    expect(onChange).toHaveBeenCalledWith("manager");
  });

  it("supports RTL arrow-key navigation", () => {
    const onChange = vi.fn();
    render(<UserRoleTabs items={items} value="all" onChange={onChange} />);

    fireEvent.keyDown(screen.getByRole("tab", { name: /الكل/ }), { key: "ArrowLeft" });

    expect(onChange).toHaveBeenCalledWith("manager");
  });
});
