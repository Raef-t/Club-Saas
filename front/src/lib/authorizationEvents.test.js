import { afterEach, describe, expect, it, vi } from "vitest";
import {
  AUTHORIZATION_DENIED_EVENT,
  getAuthorizationDeniedDetails,
  publishAuthorizationDenied,
} from "./authorizationEvents";

describe("authorization denied events", () => {
  afterEach(() => {
    vi.restoreAllMocks();
  });

  it("preserves the backend message and missing permission", () => {
    expect(
      getAuthorizationDeniedDetails({
        data: {
          message: "لا يمكنك حذف هذا العضو.",
          permission: "member.delete",
        },
      }),
    ).toEqual({
      message: "لا يمكنك حذف هذا العضو.",
      permission: "member.delete",
    });
  });

  it("publishes one browser event and logs the missing permission", () => {
    const listener = vi.fn();
    const warning = vi.spyOn(console, "warn").mockImplementation(() => {});
    window.addEventListener(AUTHORIZATION_DENIED_EVENT, listener);

    publishAuthorizationDenied({
      data: {
        message: "ممنوع",
        permission: "coach.delete",
      },
    });

    expect(listener).toHaveBeenCalledOnce();
    expect(listener.mock.calls[0][0].detail).toEqual({
      message: "ممنوع",
      permission: "coach.delete",
    });
    expect(warning).toHaveBeenCalledWith("Missing Permission: coach.delete");

    window.removeEventListener(AUTHORIZATION_DENIED_EVENT, listener);
  });
});
