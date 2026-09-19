import { describe, expect, it } from "vitest";
import { createMemberPhotoFormData } from "./useMembers";

describe("member photo payload", () => {
  it("uses the photo field required by the dedicated member photo endpoint", () => {
    const photo = new File(["image"], "member.jpg", { type: "image/jpeg" });

    expect(createMemberPhotoFormData(photo).get("photo")).toBe(photo);
    expect([...createMemberPhotoFormData(null).entries()]).toEqual([]);
  });
});
