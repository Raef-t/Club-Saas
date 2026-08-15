import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { CoachSubscriptionsDonut } from "./ManagementDashboardWidgets";

describe("coach subscriptions donut", () => {
  it("shows zero-value coaches and displays their tooltip on hover", () => {
    render(
      <CoachSubscriptionsDonut
        items={[
          {
            id: "active-coach",
            label: "Active Coach",
            value: 13,
            activities: ["Ballet"],
            color: "#16e79b",
          },
          {
            id: "empty-coach",
            label: "Empty Coach",
            value: 0,
            activities: [],
            color: "#fb7185",
          },
        ]}
      />,
    );

    expect(screen.getByText("Active Coach")).toBeInTheDocument();
    expect(screen.getByText("Empty Coach")).toBeInTheDocument();

    const tooltipSlot = screen.getByTestId("coach-tooltip-slot");
    expect(tooltipSlot).toHaveClass("h-14");
    expect(screen.queryByRole("tooltip")).not.toBeInTheDocument();

    fireEvent.mouseEnter(screen.getByTitle(/Empty Coach/));

    const tooltip = screen.getByRole("tooltip");
    expect(tooltip).toHaveTextContent("Empty Coach");
    expect(tooltipSlot).toHaveClass("h-14");
  });

  it("renders a valid empty ring when every coach has zero players", () => {
    const { container } = render(
      <CoachSubscriptionsDonut
        items={[
          {
            id: "empty-coach",
            label: "Empty Coach",
            value: 0,
            activities: [],
            color: "#fb7185",
          },
        ]}
      />,
    );

    expect(container).toHaveTextContent("Empty Coach");
    expect(container.querySelector('svg[role="img"]')).toHaveAccessibleName(/توزيع اللاعبين/);
    expect(container.innerHTML).not.toContain("NaN");
  });
});
