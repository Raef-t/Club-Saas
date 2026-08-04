import { render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import ScheduleTable from "./ScheduleTable";

describe("ScheduleTable", () => {
  it("renders backend values without editing controls", () => {
    const { container } = render(
      <ScheduleTable
        title="الفترة الصباحية"
        periodKey="morning"
        slots={[{ key: "1000", from: "10:00", to: "11:00" }]}
        scheduleData={{ sun: { morning_1000: "لياقة - سارة" } }}
      />,
    );

    expect(screen.getByText("لياقة - سارة")).toBeInTheDocument();
    expect(container.querySelector("button, input, textarea, select")).toBeNull();
  });

  it("starts with Saturday and marks a weekly holiday", () => {
    const { container } = render(
      <ScheduleTable
        title="الفترة الصباحية"
        periodKey="morning"
        slots={[{ key: "1000", from: "10:00", to: "11:00" }]}
        scheduleData={{}}
        holidayDayKeys={["fri"]}
      />,
    );

    const rows = container.querySelectorAll("tbody tr");
    expect(rows[0]).toHaveTextContent("السبت");
    expect(screen.getByText("عطلة").closest("tr")).toHaveClass("schedule-holiday-row");
    expect(screen.getByText("عطلة").closest("tr")).toHaveTextContent("الجمعة");
  });
});
