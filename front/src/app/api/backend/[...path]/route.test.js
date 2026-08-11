// @vitest-environment node

import { afterEach, beforeEach, describe, expect, it, vi } from "vitest";
import { NextRequest } from "next/server";
import { GET, POST } from "./route";

describe("backend proxy streaming", () => {
  beforeEach(() => {
    vi.stubEnv("API_BASE_URL", "https://api.example.com");
  });

  afterEach(() => {
    vi.restoreAllMocks();
    vi.unstubAllEnvs();
  });

  it("temporarily forwards to a remote HTTP backend outside production", async () => {
    vi.stubEnv("API_BASE_URL", "http://203.0.113.10");
    const fetchMock = vi.spyOn(globalThis, "fetch").mockResolvedValue(
      Response.json({
        status: "success",
        data: { access_token: "test-access-token" },
      }),
    );
    const request = new NextRequest("http://localhost/api/backend/auth/login", {
      method: "POST",
      headers: {
        Origin: "http://localhost",
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ username: "test-user", password: "test-password" }),
    });

    const response = await POST(request, {
      params: Promise.resolve({ path: ["auth", "login"] }),
    });

    expect(response.status).toBe(200);
    expect(response.headers.get("cache-control")).toBe("no-store");
    expect(fetchMock).toHaveBeenCalledOnce();
  });

  it("forwards SSE responses without buffering them", async () => {
    const event =
      'event: dashboard_updated\ndata: {"status":"success","data":{"total_active_subscribed_members":10}}\n\n';
    const upstreamStream = new ReadableStream({
      start(controller) {
        controller.enqueue(new TextEncoder().encode(event));
      },
    });
    const fetchMock = vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(upstreamStream, {
        headers: { "Content-Type": "text/event-stream; charset=utf-8" },
      }),
    );
    const request = new NextRequest(
      "http://localhost/api/backend/attendance-manager/dashboard-stats-stream?branch_id=5",
      {
        headers: {
          Accept: "text/event-stream",
          Authorization: "Bearer test-token",
        },
      },
    );

    const response = await GET(request, {
      params: Promise.resolve({ path: ["attendance-manager", "dashboard-stats-stream"] }),
    });

    expect(response.headers.get("content-type")).toContain("text/event-stream");
    expect(response.headers.get("cache-control")).toContain("no-store");
    expect(response.headers.get("x-accel-buffering")).toBe("no");

    const upstreamHeaders = fetchMock.mock.calls[0][1].headers;
    expect(upstreamHeaders.get("accept")).toBe("text/event-stream");
    expect(upstreamHeaders.get("accept-encoding")).toBe("identity");

    const reader = response.body.getReader();
    const firstChunk = await reader.read();
    expect(new TextDecoder().decode(firstChunk.value)).toBe(event);
    await reader.cancel();
  });

  it("forwards binary responses without converting them to text", async () => {
    const binaryPayload = new Uint8Array([0, 37, 80, 68, 70, 255]);
    vi.spyOn(globalThis, "fetch").mockResolvedValue(
      new Response(binaryPayload, {
        headers: { "Content-Type": "application/pdf" },
      }),
    );
    const request = new NextRequest("http://localhost/api/backend/reports/export", {
      headers: { Authorization: "Bearer test-token" },
    });

    const response = await GET(request, {
      params: Promise.resolve({ path: ["reports", "export"] }),
    });

    expect(response.headers.get("content-type")).toBe("application/pdf");
    expect(new Uint8Array(await response.arrayBuffer())).toEqual(binaryPayload);
  });
});
