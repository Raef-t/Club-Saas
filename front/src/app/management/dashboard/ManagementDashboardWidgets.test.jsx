import { fireEvent, render, screen } from "@testing-library/react";
import { describe, expect, it } from "vitest";
import { CoachSubscriptionsDonut } from "./ManagementDashboardWidgets";

describe("coach subscriptions donut", () => {
  it("hides zero-value coaches and shows a detailed tooltip on hover", () => {
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
    expect(screen.queryByText("Empty Coach")).not.toBeInTheDocument();

    fireEvent.mouseEnter(screen.getByLabelText(/Active Coach/));

    const tooltip = screen.getByRole("tooltip");
    expect(tooltip).toHaveTextContent("Active Coach");
    expect(tooltip).toHaveTextContent("Ballet");
  });
});
