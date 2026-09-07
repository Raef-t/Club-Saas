import { cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it } from "vitest";
import SubscriptionDetails from "./SubscriptionDetails";

describe("subscription details", () => {
  afterEach(() => cleanup());

  it("shows the member generated username in the identity header and member details", () => {
    render(
      <SubscriptionDetails
        subscription={{
          id: 10,
          status: "active",
          member: {
            member_number: "MEM-2026-0034",
            generated_username: "tec-ply-18755",
            person: { full_name: "انجي مؤذن", phone: "955430964" },
          },
          plan: { name: "أجهزة عام" },
          items: [],
        }}
        showActions={false}
      />,
    );

    expect(screen.getAllByText("tec-ply-18755")).toHaveLength(2);
    expect(screen.getByText("اسم المستخدم المولّد")).toBeInTheDocument();
    expect(screen.getByText("MEM-2026-0034")).toBeInTheDocument();
  });

  it("uses the profile member as a fallback for subscription summaries", () => {
    render(
      <SubscriptionDetails
        subscription={{
          id: 11,
          status: "active",
          member: { id: 7 },
          plan: {},
          items: [],
        }}
        memberFallback={{
          member_number: "MEM-11",
          generated_username: "tec-ply-11000",
          person: { full_name: "لاعب تجريبي" },
        }}
        showActions={false}
      />,
    );

    expect(screen.getAllByText("tec-ply-11000")).toHaveLength(2);
    expect(screen.getAllByText("لاعب تجريبي")).toHaveLength(2);
  });
});
