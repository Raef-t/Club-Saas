import { render, screen, fireEvent } from "@testing-library/react";
import { describe, expect, it, vi } from "vitest";
import StatCard from "./StatCard";

describe("StatCard component", () => {
  it("renders title, value, and helper text", () => {
    render(
      <StatCard
        title="المشتركون النشطون"
        value="120"
        helper="اشتراكات نشطة"
        tone="yellow"
      />,
    );

    expect(screen.getByText("المشتركون النشطون")).toBeInTheDocument();
    expect(screen.getByText("120")).toBeInTheDocument();
    expect(screen.getByText("اشتراكات نشطة")).toBeInTheDocument();
  });

  it("renders as an interactive button when onClick is passed and triggers handler", () => {
    const handleClick = vi.fn();
    render(
      <StatCard
        title="الاشتراكات النشطة"
        value="45"
        onClick={handleClick}
        active={true}
      />,
    );

    const button = screen.getByRole("button", { name: /تصفية حسب الاشتراكات النشطة/i });
    expect(button).toBeInTheDocument();

    fireEvent.click(button);
    expect(handleClick).toHaveBeenCalledTimes(1);
  });

  it("renders as a Link when href is passed", () => {
    render(
      <StatCard
        title="اللاعبون في التدريب"
        value="12"
        href="/management/attendance?status=checked_in"
      />,
    );

    const link = screen.getByRole("link", { name: /فتح صفحة اللاعبون في التدريب/i });
    expect(link).toHaveAttribute("href", "/management/attendance?status=checked_in");
  });
});
