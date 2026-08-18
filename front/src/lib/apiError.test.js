import { describe, expect, it } from "vitest";
import { getApiErrorMessage, getApiFieldErrors } from "./apiError";

describe("getApiErrorMessage", () => {
  it("prefers the backend message", () => {
    expect(getApiErrorMessage({ data: { message: "Invalid member" } })).toBe("Invalid member");
  });

  it("translates maximum capacity errors to Arabic", () => {
    expect(
      getApiErrorMessage({
        data: { message: ".This subscription plan has reached its maximum capacity" },
      }),
    ).toBe("هذه الفعالية مسددة (وصلت خطة الاشتراك إلى الحد الأقصى من المشتركين).");

    expect(
      getApiErrorMessage({
        data: { message: "This subscription plan has reached its maximum capacity" },
      }),
    ).toBe("هذه الفعالية مسددة (وصلت خطة الاشتراك إلى الحد الأقصى من المشتركين).");
  });

  it("uses the supplied fallback for unknown errors", () => {
    expect(getApiErrorMessage(null, "Try again")).toBe("Try again");
  });
});

describe("getApiFieldErrors", () => {
  it("normalizes arrays and string field messages", () => {
    expect(
      getApiFieldErrors({
        data: {
          errors: {
            name: ["Name is required"],
            phone: "Phone is invalid",
          },
        },
      }),
    ).toEqual({
      name: "Name is required",
      phone: "Phone is invalid",
    });
  });
});
