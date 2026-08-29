import { describe, expect, it } from "vitest";
import { createBackendApi } from "./baseQuery";

describe("createBackendApi", () => {
  it("re-fetches cached queries whenever a page subscribes again", () => {
    const api = createBackendApi({
      reducerPath: "freshOnMountTestApi",
      endpoints: (builder) => ({
        getResource: builder.query({ query: () => "resource" }),
      }),
    });

    const initialState = api.reducer(undefined, { type: "@@INIT" });

    expect(initialState.config.refetchOnMountOrArgChange).toBe(true);
  });

  it("allows an API slice to explicitly override the shared default", () => {
    const api = createBackendApi({
      reducerPath: "overrideFreshOnMountTestApi",
      refetchOnMountOrArgChange: false,
      endpoints: (builder) => ({
        getResource: builder.query({ query: () => "resource" }),
      }),
    });

    const initialState = api.reducer(undefined, { type: "@@INIT" });

    expect(initialState.config.refetchOnMountOrArgChange).toBe(false);
  });
});
