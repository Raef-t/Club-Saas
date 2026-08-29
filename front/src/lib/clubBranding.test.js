import { describe, expect, it } from "vitest";
import {
  DEFAULT_BRAND_LOGO_URL,
  getBrandClubs,
  resolveClubLogoUrl,
  selectBrandClub,
} from "./clubBranding";

describe("club branding", () => {
  const clubs = [
    { id: 1, logo_url: "https://api.example.com/storage/one.png", is_active: false },
    { id: 2, logo: "storage/two.png", is_active: true, updated_at: "2026-08-27" },
  ];

  it("normalizes club collections and selects the branch club", () => {
    expect(getBrandClubs({ data: { data: clubs } })).toEqual(clubs);
    expect(selectBrandClub(clubs, "1")).toBe(clubs[0]);
    expect(selectBrandClub(clubs, null)).toBe(clubs[1]);
  });

  it("uses an embedded branch club while the club query is loading", () => {
    const embeddedClub = { id: 7, logo: "storage/embedded.png" };
    expect(selectBrandClub([], "7", embeddedClub)).toBe(embeddedClub);
  });

  it("proxies backend asset paths and keeps the bundled fallback", () => {
    expect(resolveClubLogoUrl(clubs[0])).toBe("/api/assets/storage/one.png");
    expect(resolveClubLogoUrl(clubs[1])).toBe("/api/assets/storage/two.png?v=2026-08-27");
    expect(resolveClubLogoUrl(null)).toBe(DEFAULT_BRAND_LOGO_URL);
  });
});
