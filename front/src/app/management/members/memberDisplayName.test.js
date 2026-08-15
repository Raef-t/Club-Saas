import { describe, expect, it } from "vitest";
import { getMemberDisplayName } from "./memberDisplayName";

describe("member display name", () => {
  it("uses the nested full name returned by the members API", () => {
    expect(
      getMemberDisplayName({
        id: 12,
        person: { full_name: "محمد الأحمد" },
      }),
    ).toBe("محمد الأحمد");
  });

  it("builds the name from nested name parts when full_name is missing", () => {
    expect(
      getMemberDisplayName({
        id: 12,
        person: { first_name: "محمد", last_name: "الأحمد" },
      }),
    ).toBe("محمد الأحمد");
  });

  it("falls back to the member number instead of showing undefined", () => {
    expect(getMemberDisplayName({ id: 12 })).toBe("العضو #12");
  });
});
