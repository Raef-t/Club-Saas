import { describe, expect, it } from "vitest";
import { createLockerStaffOptions, getLockerHolderLabel } from "./lockerUtils";

describe("locker staff selectors", () => {
  it("builds staff choices using names while preserving staff ids", () => {
    expect(
      createLockerStaffOptions({
        data: {
          data: [
            { id: 58, person: { full_name: "يمان عبدالله" } },
            { id: 60, person: { first_name: "سارة", last_name: "محمد" } },
          ],
        },
      }),
    ).toEqual([
      { value: "58", label: "يمان عبدالله" },
      { value: "60", label: "سارة محمد" },
    ]);
  });

  it("shows the selected staff name on occupied locker cards", () => {
    expect(
      getLockerHolderLabel(
        { status: "with_staff", holder_type: "staff", holder_id: 58 },
        [],
        [{ value: "58", label: "يمان عبدالله" }],
      ),
    ).toBe("يمان عبدالله");
  });
});
