import { describe, expect, it } from "vitest";
import {
  createClubFormValues,
  createClubPayload,
  createClubStats,
  filterClubs,
  getClubCollection,
  getClubRecord,
  hasDuplicateClubName,
} from "./clubUtils";

const clubs = [
  { id: 1, name: { ar: "تكنوجيم", en: "TechnoGym" }, is_active: true },
  { id: 2, name: "Power", is_active: false },
];

describe("club utilities", () => {
  it("normalizes collection and record response shapes", () => {
    expect(getClubCollection({ data: { data: clubs } })).toEqual(clubs);
    expect(getClubRecord({ data: clubs[0] })).toEqual(clubs[0]);
  });

  it("filters localized names and status labels", () => {
    expect(filterClubs(clubs, "تكنو")).toEqual([clubs[0]]);
    expect(filterClubs(clubs, "غير نشط")).toEqual([clubs[1]]);
  });

  it("creates club statistics", () => {
    expect(createClubStats(clubs).map((item) => Number(item.value))).toEqual([2, 1, 1]);
  });

  it("creates editor values and a normalized payload", () => {
    const form = createClubFormValues({
      ...clubs[0],
      logo_url: "https://example.com/logo.png",
    });

    expect(form.name).toBe("تكنوجيم");
    expect(createClubPayload({ ...form, logo_url: " " })).toEqual({
      name: "تكنوجيم",
      logo_url: null,
      is_active: true,
    });
  });

  it("detects duplicate names while excluding the edited club", () => {
    expect(hasDuplicateClubName(clubs, " تكنوجيم ")).toBe(true);
    expect(hasDuplicateClubName(clubs, "تكنوجيم", 1)).toBe(false);
  });
});
