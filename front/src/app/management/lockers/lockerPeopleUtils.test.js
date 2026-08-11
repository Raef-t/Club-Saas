import { describe, expect, it } from "vitest";
import {
  createLockerCoachOptions,
  createLockerStaffOptions,
  getLockerHolderLabel,
} from "./lockerUtils";

describe("locker staff selectors", () => {
  it("builds coach choices and resolves the selected coach name", () => {
    const coachOptions = createLockerCoachOptions({
      data: [{ id: 41, person: { full_name: "أحمد المدرب" } }],
    });

    expect(coachOptions).toEqual([{ value: "41", label: "أحمد المدرب" }]);
    expect(
      getLockerHolderLabel(
        { status: "assigned", holder_type: "coach", holder_id: 41 },
        [],
        coachOptions,
      ),
    ).toBe("أحمد المدرب");
  });

  it("shows a coach name returned inside the current reservation", () => {
    expect(
      getLockerHolderLabel({
        status: "with_coach",
        current_reservation: {
          holder_type: "coach",
          holder_id: 41,
          holder: { person: { full_name: "أحمد المدرب" } },
        },
      }),
    ).toBe("أحمد المدرب");
  });

  it("resolves a coach relation when the response only exposes the locker status", () => {
    expect(
      getLockerHolderLabel({
        status: "with_coach",
        coach: { id: 41, person: { first_name: "أحمد", last_name: "المدرب" } },
      }),
    ).toBe("أحمد المدرب");
  });

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
        [],
        [{ value: "58", label: "يمان عبدالله" }],
      ),
    ).toBe("يمان عبدالله");
  });
});
