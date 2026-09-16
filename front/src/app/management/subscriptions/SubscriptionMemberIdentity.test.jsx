import { cleanup, render, screen } from "@testing-library/react";
import { afterEach, describe, expect, it } from "vitest";
import SubscriptionMemberIdentity from "./SubscriptionMemberIdentity";

describe("subscription member identity", () => {
  afterEach(() => cleanup());

  it("shows only the phone number below the member name", () => {
    render(
      <SubscriptionMemberIdentity
        subscription={{
          member: {
            generated_username: "tec-ply-12345",
            person: { full_name: "ياسمين صونو", phone: "936345942" },
          },
        }}
      />,
    );

    expect(screen.getByText("ياسمين صونو")).toBeInTheDocument();
    expect(screen.getByText("936345942")).toBeInTheDocument();
    expect(screen.queryByText("tec-ply-12345")).not.toBeInTheDocument();
    expect(screen.queryByText("·")).not.toBeInTheDocument();
  });

  it("does not add a dash when the phone number is unavailable", () => {
    render(
      <SubscriptionMemberIdentity
        subscription={{ member: { person: { full_name: "دعاء صباغ" } } }}
      />,
    );

    expect(screen.getByText("دعاء صباغ")).toBeInTheDocument();
    expect(screen.queryByText("-")).not.toBeInTheDocument();
  });
});
