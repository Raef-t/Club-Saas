import { describe, expect, it } from "vitest";
import { getMemberAccountName, getMemberCreatorUsername } from "./memberIdentity";

describe("getMemberAccountName", () => {
  it("prefers the player-selected username over the generated username", () => {
    expect(
      getMemberAccountName({
        custom_username: "dania.player",
        generated_username: "tec-ply-10007",
      }),
    ).toBe("dania.player");
  });

  it("falls back to the generated username without using the membership number", () => {
    expect(
      getMemberAccountName({
        member_number: "MEM-2026-0149",
        generated_username: "tec-ply-10149",
      }),
    ).toBe("tec-ply-10149");
  });

  it("supports nested and split API response shapes", () => {
    expect(
      getMemberAccountName(
        { member: { generated_username: "tec-ply-22" } },
        { custom_username: "assigned.player" },
      ),
    ).toBe("assigned.player");
  });

  it("returns an empty value when only an internal membership number is available", () => {
    expect(getMemberAccountName({ member_number: "MEM-2026-0149" })).toBe("");
  });
});

describe("getMemberCreatorUsername", () => {
  it("prefers the creator username over the creator display name", () => {
    expect(
      getMemberCreatorUsername({
        created_by: { username: "reception.ahmad", name: "أحمد محمد" },
      }),
    ).toBe("reception.ahmad");
  });

  it("supports registered-by and direct username response shapes", () => {
    expect(getMemberCreatorUsername({ registered_by: { custom_username: "front.desk" } })).toBe(
      "front.desk",
    );
    expect(getMemberCreatorUsername({ created_by_username: "club.admin" })).toBe("club.admin");
  });

  it("keeps the creator name as a fallback for legacy responses", () => {
    expect(getMemberCreatorUsername({ created_by: { name: "ISS Group" } })).toBe("ISS Group");
  });

  it("returns an empty value when the response has no creator", () => {
    expect(getMemberCreatorUsername({ id: 7 })).toBe("");
  });
});
