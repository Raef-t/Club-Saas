import { describe, expect, it } from "vitest";
import { extractCreatedAccount } from "./generatedAccount";

describe("extractCreatedAccount", () => {
  it("reads member credentials from a wrapped member response", () => {
    const result = extractCreatedAccount(
      {
        data: {
          member: { id: 25, generated_username: "tec-ply-100" },
          credentials: { generated_password: "secret-1" },
        },
      },
      { entityKeys: ["member"] },
    );

    expect(result).toEqual({
      id: 25,
      username: "tec-ply-100",
      password: "secret-1",
    });
  });

  it("reads coach credentials from the main data record", () => {
    const result = extractCreatedAccount(
      {
        data: {
          id: 53,
          username: "tec-coach-200",
          generated_password: "secret-2",
        },
      },
      { entityKeys: ["coach", "staff"] },
    );

    expect(result).toEqual({
      id: 53,
      username: "tec-coach-200",
      password: "secret-2",
    });
  });
});
